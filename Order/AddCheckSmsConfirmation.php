<?php

class AddCheckSmsConfirmation
{
    /**
     * @var Orders
     */
    protected $ordersService;

    protected $regenerate = false;

    /**
     * @var UpdateConfirmCodesForSms
     */
    protected $updateConfirmCodesForSmsAction;

    protected $sources = [Order::SOURCE_API, Order::SOURCE_FORM];

    /**
     * AddCheckSmsConfirmation constructor.
     * @param Orders $ordersService
     * @param UpdateConfirmCodesForSms $updateConfirmCodesForSmsAction
     */
    public function __construct(Orders $ordersService, UpdateConfirmCodesForSms $updateConfirmCodesForSmsAction)
    {
        $this->updateConfirmCodesForSmsAction = $updateConfirmCodesForSmsAction;
        $this->ordersService = $ordersService;
    }

    public function handle(Order $order)
    {
        if (!in_array($order->getSource(), $this->sources)) {
            return false;
        }

        $list = $this->ordersService->getOrderCheckRepository()->getForOrderCodeKey($order);
        if (array_key_exists(OrderCheck::CODE_SMS_CONFIRMATION, $list)) {
            if ($this->regenerate) {
                //$this->setValues($list[OrderCheck::CODE_SMS_CONFIRMATION], false);
                $this->updateConfirmCodesForSmsAction->handle($list[OrderCheck::CODE_SMS_CONFIRMATION]);
                $this->ordersService->getEntityManager()->flush();
            }
            return false;
        }
        $check = $this->ordersService->getOrderCheckEntity($order, OrderCheck::CODE_SMS_CONFIRMATION);
        $this->updateConfirmCodesForSmsAction->handle($check);
        $this->ordersService->getEntityManager()->flush();
        return true;
    }

    /**
     * @param bool $regenerate
     * @return $this
     */
    public function setRegenerate(bool $regenerate): self
    {
        $this->regenerate = $regenerate;
        return $this;
    }
}
