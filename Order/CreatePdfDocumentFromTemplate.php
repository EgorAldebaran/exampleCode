<?php

class CreatePdfDocumentFromTemplate
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var BuildArrayWithOrderData
     */
    protected $buildArrayWithOrderDataAction;

    protected $orderPropertiesArray;

    /**
     * @var OrderPaymentRequestService
     */
    private $orderPaymentRequestService;

    /**
     * @var GetData
     */
    protected $getOrderFileDataAction;

    /**
     * @var CreatePayment
     */
    private $createPaymentAction;

    public function __construct(
        BuildArrayWithOrderData $buildArrayWithOrderDataAction,
        GetData $getOrderFileDataAction,
        Environment $twig,
        OrderPaymentRequestService $orderPaymentRequestService,
        CreatePayment $createPaymentAction
    ) {
        $this->getOrderFileDataAction = $getOrderFileDataAction;
        $this->buildArrayWithOrderDataAction = $buildArrayWithOrderDataAction;
        $this->twig = $twig;
        $this->orderPaymentRequestService = $orderPaymentRequestService;
        $this->createPaymentAction = $createPaymentAction;
    }

    protected $orderPropertiesAsArray = null;

    /**
     * @var OrganisationBrandOrderPropertyFile
     */
    protected $brandPropertyFile;

    public function handle(Order $order)
    {
        $this->orderPropertiesAsArray = null;
        $contentOrigin = $this->brandPropertyFile->getData();
        if (!$contentOrigin) {
            return '';
        }

        if (is_resource($contentOrigin)) {
            $contentOrigin = stream_get_contents($contentOrigin, -1, 0);
        }

        $this->orderPropertiesArray = $this->getOrderPropertiesAsArray($order);

        //https://www.setasign.com/products/fpdi/manual/#p-104

        // https://manuals.setasign.com/fpdi-manual/v2/the-fpdi-class/examples/#index-3
        $pdf = new Fpdi();
        $pdf->AddPage();
        $stream = StreamReader::createByString($contentOrigin);
        $pageCount = $pdf->setSourceFile($stream);

        $tplIdx = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplIdx);
        //$pdf->AddPage($size['orientation'], $size);
        $pdf->useTemplate($tplIdx, $size);
        //$pdf->useTemplate($tplIdx, 10, 10, 100);
        $pdf->SetFont('Helvetica');

        // http://www.fpdf.org/en/doc/image.htm

        $this->handlePage(1, $pdf, $order);

        /*
        $pageNo = 1;
        foreach ($this->brandPropertyFile->getPlaceholders() as $placeholder) {
            if (!array_key_exists('code', $placeholder)) {
                continue;
            }
            $page = (int)($pageCount['page'] ?? 1);
            if ($page > $pageNo) {
                continue;
            }

            if (strpos($placeholder['code'], '{{image_') === 0) {
                $this->addImagePlaceholder($placeholder, $pdf);
                continue;
            }

            $valueFromOrder = $this->getPlaceholderValueFromOrder($placeholder['code']);
            if (!$valueFromOrder) {
                continue;
            }

            if (!($font = $placeholder['font'] ?? false)) {
                $font = 'Helvetica';
            }

            if (!($size = $placeholder['size'] ?? false)) {
                $size = 10;
            }

            if (!($style = $placeholder['style'] ?? false)) {
                $style = '';
            }

            // http://www.fpdf.org/en/doc/setfont.htm
            $pdf->SetFont($font, $style, $size);

            if ($value = $placeholder['color'] ?? null) {
                $exploder = new StringContainer(new Numbers());
                $colorParts = $exploder->explode($placeholder['color']);
                $colorParts = array_pad($colorParts, 3, 0);
                $pdf->SetTextColor($colorParts[0], $colorParts[1], $colorParts[2]);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->SetXY($placeholder['x'] ?? 50, $placeholder['y'] ?? 50);
            //https://stackoverflow.com/questions/3514076/special-characters-in-fpdf-with-php
            $valueFromOrder = iconv('UTF-8', 'windows-1252', $valueFromOrder);
            // http://www.fpdf.org/en/doc/write.htm
            $pdf->Write(10, $valueFromOrder);
        }
        */

        if ($pageCount > 1) {
            for ($pageNo = 2; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $pdf->AddPage();
                $pdf->useTemplate($templateId, ['adjustPageSize' => true]);
                $this->handlePage($pageNo, $pdf, $order);
            }
        }

        // http://www.fpdf.org/en/doc/output.htm
        $content = $pdf->Output('S');

        return $content;
    }

    protected function handlePage($pageNo, Fpdi $pdf, Order $order)
    {
        foreach ($this->brandPropertyFile->getPlaceholders() as $placeholder) {
            if (!array_key_exists('code', $placeholder)) {
                continue;
            }

            $page = (int)($placeholder['page'] ?? 1);
            if (!$page) {
                $page = 1;
            }

            if ($page != $pageNo) {
                continue;
            }

            if (strpos($placeholder['code'], '{{image_') === 0) {
                $this->addImagePlaceholder($placeholder, $pdf);
                continue;
            }

            if (strpos($placeholder['code'], '{{link_') === 0) {
                $this->addLinkPlaceholder($placeholder, $pdf, $order);
                continue;
            }

            if ($placeholder['code'] === '{{loop_order_item_name}}') {
                $this->addLoopedItemsPlaceholder($placeholder, $pdf, 'name');
                continue;
            }

            if ($placeholder['code'] === '{{loop_order_item_amount}}') {
                $this->addLoopedItemsPlaceholder($placeholder, $pdf, 'amount');
                continue;
            }

            if ($placeholder['code'] === '{{loop_order_item_sku}}') {
                $this->addLoopedItemsPlaceholder($placeholder, $pdf, 'sku');
                continue;
            }

            if ($placeholder['code'] === '{{loop_order_item_price}}') {
                $this->addLoopedItemsPlaceholder($placeholder, $pdf, 'price');
                continue;
            }

            if ($placeholder['code'] === '{{loop_order_item_tax}}') {
                $this->addLoopedItemsPlaceholder($placeholder, $pdf, 'tax');
                continue;
            }

            if ($placeholder['code'] === '{{loop_order_item_tax_rate}}') {
                $this->addLoopedItemsPlaceholder($placeholder, $pdf, 'tax_rate');
                continue;
            }

            if ($placeholder['code'] === '{{loop_order_item_total_price}}') {
                $this->addLoopedItemsPlaceholder($placeholder, $pdf, 'total_price');
                continue;
            }

            $valueFromOrder = $this->getPlaceholderValueFromOrder($placeholder['code']);
            if (!$valueFromOrder) {
                continue;
            }

            if (!($font = $placeholder['font'] ?? false)) {
                $font = 'Helvetica';
            }

            if (!($size = $placeholder['size'] ?? false)) {
                $size = 10;
            }

            if (!($style = $placeholder['style'] ?? false)) {
                $style = '';
            }

            // http://www.fpdf.org/en/doc/setfont.htm
            $pdf->SetFont($font, $style, $size);

            if ($value = $placeholder['color'] ?? null) {
                $exploder = new StringContainer(new Numbers());
                $colorParts = $exploder->explode($placeholder['color']);
                $colorParts = array_pad($colorParts, 3, 0);
                $pdf->SetTextColor($colorParts[0], $colorParts[1], $colorParts[2]);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            $pdf->SetXY($placeholder['x'] ?? 50, $placeholder['y'] ?? 50);
            //https://stackoverflow.com/questions/3514076/special-characters-in-fpdf-with-php

            $valueFromOrder = iconv('UTF-8', 'windows-1252', $valueFromOrder);

            // http://www.fpdf.org/en/doc/write.htm
            $pdf->Write(10, $valueFromOrder);
        }
    }

    protected function addImagePlaceholder(array $placeholder, Fpdi $pdf)
    {
        $matches = [];
        $code = preg_match_all('|\{\{(image_[_a-z0-9]+)\}\}|ui', $placeholder['code'], $matches);
        if (!$code) {
            return;
        }
        $imagesCodes = $matches[1];
        if (!is_array($imagesCodes)) {
            return;
        }
        foreach ($imagesCodes as $imagesCode) {
            $image = $this->orderPropertiesArray[$imagesCode] ?? null;
            if (!$image) {
                continue;
            }

            $img = $this->getOrderFileDataAction->handle($image);
            if (!$img) {
                continue;
            }

            $type = 'jpg';

            // Help: https://gist.github.com/timneutkens/e7b1b20e976dff3d4e8408f4979cac6d
            $pic = 'data://text/plain;base64,' . base64_encode($img);
            $info = getimagesize($pic);

            if (!($size = $placeholder['size'] ?? false)) {
                $size = 180;
            }

            $types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];
            $type = $types[$info['mime']] ?? false;
            if (!$type) {
                continue;
            }

            $pdf->Image($pic, $placeholder['x'] ?? 50, $placeholder['y'] ?? 50, $size, 0, $type);
            return; // to first success
        }
    }

    protected function addLinkPlaceholder(array $placeholder, Fpdi $pdf, Order $order): void
    {
        $stripePaymentSystem = $this->orderPaymentRequestService->getPaymentSystemRepository()->findByAdapterCode(PaymentSystemDictionary::STRIPE);
        if (null === $stripePaymentSystem) {
            return;
        }

        $payment = $this->createPaymentAction->handle($stripePaymentSystem, $order);
        if ($this->createPaymentAction->getError()) {
            return;
        }

        $xCoordinateStart = $placeholder['x'];
        $yCoordinateStart = $placeholder['y'];

        $pdf->SetXY($xCoordinateStart, $yCoordinateStart);
        $pdf->SetFont(
            empty($placeholder['font']) ? 'Helvetica' : $placeholder['font'],
            empty($placeholder['style']) ? '' : $placeholder['style'],
            empty($placeholder['size']) ? 10 : $placeholder['size']
        );

        // @TODO temporary decision
        // $pdf->Write(10, 'pay.clevercards.nl', $this->orderPropertiesArray['link_order_stripe_payment_url']);
        $pdf->Write(
            10,
            'pay.bonifin.de',
            $payment->getUrl()
        );
    }

    protected function addLoopedItemsPlaceholder(array $placeholder, Fpdi $pdf, string $property = 'name'): void
    {
        $xCoordinateStart = $placeholder['x'];
        $yCoordinateStart = $placeholder['y'];

        foreach ($this->orderPropertiesArray['order_items'] as $orderItem) {
            $pdf->SetXY($xCoordinateStart, $yCoordinateStart);
            $pdf->SetFont(
                empty($placeholder['font']) ? 'Helvetica' : $placeholder['font'],
                empty($placeholder['style']) ? '' : $placeholder['style'],
                empty($placeholder['size']) ? 10 : $placeholder['size']
            );
            $pdf->Write(10, $orderItem[$property]);

            $yCoordinateStart += 10;
        }
    }

    protected function getPlaceholderValueFromOrder($code)
    {
        return $this->twig->createTemplate($code)->render($this->orderPropertiesArray);

        $exploder = new StringContainer(new NumbersAndLatinLettersPlus());
        //$codes = $exploder->explode($code);
        //$orderArray = $this->getOrderPropertiesAsArray($order);

        $resultPars = [];
        foreach ($codes as $code) {
            if (preg_match('|{{|', $code)) {
                $codeAsTemplate = $this->twig->createTemplate($code)->render($orderArray);
                if ($codeAsTemplate) {
                    $resultPars[] = $orderArray[$code];
                }
            } else if (array_key_exists($code, $orderArray)) {
                $resultPars[] = $orderArray[$code];
            }
        }
        return implode(' ', $resultPars);
    }

    protected function getOrderPropertiesAsArray($order)
    {
        if ($this->orderPropertiesAsArray === null) {
            $extractor = $this->buildArrayWithOrderDataAction;
            $extractor->setDatetimeToString(false);
            $this->orderPropertiesAsArray = $extractor->handle($order);
        }
        return $this->orderPropertiesAsArray;
    }

    /**
     * @return OrganisationBrandOrderPropertyFile
     */
    public function getBrandPropertyFile(): OrganisationBrandOrderPropertyFile
    {
        return $this->brandPropertyFile;
    }

    /**
     * @param OrganisationBrandOrderPropertyFile $brandPropertyFile
     * @return $this
     */
    public function setBrandPropertyFile(OrganisationBrandOrderPropertyFile $brandPropertyFile): self
    {
        $this->brandPropertyFile = $brandPropertyFile;
        return $this;
    }
}
