<?php

class Order
{
    use CountryCodesTrait;

    const STATUS_NEW = 'new';
    const STATUS_CANCELED = 'canceled';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_WAIT_PERMISSION = 'wait_permission';
    const STATUS_HOLD = 'hold'; // no auto work
    const STATUS_BAD_ITEMS = 'bad_items';
    const STATUS_COMPILE_ERROR = 'compile_error';
    const STATUS_COMPILED = 'compiled';
    const STATUS_COMPILATION = 'compilation';
    const STATUS_WORKFLOW_READY = 'workflow_ready'; // ready to start workflow
    const STATUS_WORKFLOW = 'workflow'; // workflow just started
    const STATUS_WORKFLOW_STOP = 'workflow_stop'; // workflow stopped
    const STATUS_WORKFLOW_ERROR = 'workflow_error'; // workflow error
    const STATUS_IN_WORK = 'in_work';
    const STATUS_FINISHED = 'finished';
    const STATUS_COMPROMISE_COMPLETED = 'compromise_completed';
    const STATUS_TEST_ORDER = 'test_order';
    const STATUS_FAKE_ORDER = 'fake_order';
    const STATUS_CONTRACT_TERMINATED = 'contract_terminated';
    const STATUS_CONTRACT_ENDED = 'contract_ended';

    const STATUS_ORDER_CREATED = 'order_created';
    const STATUS_INVOICE_SENT = 'invoice_sent';
    const STATUS_REMINDER_1 = 'reminder1';
    const STATUS_REMINDER_2 = 'reminder2';
    const STATUS_COLLECT = 'collect';
    const STATUS_PAYMENT_COMPLETE = 'payment_complete';
    const STATUS_ORDER_COMPLETE = 'order_complete';

    const STATUS_DELETED = 'deleted';
    const STATUS_LETTER_COMPRISE = 'letter_comprise';
    const STATUS_WRONG_ORDER = 'wrong_order';

    const SOURCE_EXTERNAL_DATABASE = 'ext_db';
    const SOURCE_FORM = 'form';
    const SOURCE_API = 'api';
    const SOURCE_FILE = 'file';

    const SETTLED_STATUS_NOT_COMPLETE = 'not_complete';
    const SETTLED_STATUS_COMPLETE = 'complete';
    const SETTLED_STATUS_COMPLETE_COMPROMISE = 'complete_compromise';

    const HAS_YES = 'yes';
    const HAS_NO = 'no';
    const HAS_LOADED = 'loaded';

    const FILE_TO_SEND_BY_POST_NOT_EXIST = 'not_exist';
    const FILE_TO_SEND_BY_POST_EXIST = 'exist';
    const FILE_TO_SEND_BY_POST_WAS_SENT = 'was_sent';

    /**
     * @title Order id
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned":true})
     */
    private $id;

    /**
     * @title Full price for order
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $price;

    /**
     * @title Order date created
     *
     * @ORM\Column(type="datetime")
     */
    private $datetimeCreate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datetimeUpdate;

    /**
     * @title Order date external
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datetimeExternal;

    /**
     *
     * @ORM\Column(type="string", length=32)
     */
    private $status = 'new';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderItem", mappedBy="orderData", orphanRemoval=true)
     */
    private $items;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderProperty", mappedBy="orderData", orphanRemoval=true)
     */
    private $properties;

    /**
     * @var  OrderProperty[]|null
     */
    private $propertiesNameKey = null;

    /**
     *
     * @ORM\OneToMany(targetEntity="App\Entity\OrderTransaction", mappedBy="orderData", orphanRemoval=true)
     */
    private $transactions;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OrganisationWebSite")
     */
    private $webSite;

    /**
     * @title Order sum in debt
     *
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $debt = 0;

    /**
     * @title Order id was compiled from brand prefix and max number
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $orderId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OrderCustomerData", inversedBy="orders")
     */
    private $customerData;

    /**
     * @title Order meta data array
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $meta = [];

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderStateHistory", mappedBy="orderData", orphanRemoval=true)
     */
    private $stateHistory;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderFile", mappedBy="orderData", orphanRemoval=true)
     */
    private $files;

    /**
     * @ORM\Column(type="string", length=12, options={"default": "not_exist"})
     */
    private $fileToSendByPost = 'not_exist';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\WorkflowOrder", mappedBy="orderData", orphanRemoval=true)
     */
    private $workflowOrders;

    /**
     * @title Order status code
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $statusPublic = 'new';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderEmailToCustomer", mappedBy="orderData", orphanRemoval=true)
     */
    private $orderEmailsToCustomer;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderSmsToCustomer", mappedBy="orderData", orphanRemoval=true)
     */
    private $orderSmsToCustomer;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderNotice", mappedBy="orderData", orphanRemoval=true)
     */
    private $notices;

    /**
     * @title IP address
     *
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $ip = null;

    /**
     * @title User agent
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $userAgent = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $cookie = [];

    /**
     * @title Source type code: api, ext_db
     *
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $source;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $sourceCode;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datetimeImport;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $circumstances = [];

    /**
     * @title External order ID
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $sourceOrderId;

    /**
     * @title Domain
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $domain;

    /**
     * @title Credit request info array
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $creditRequestInfo = [];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OrganisationBrand", inversedBy="orders")
     */
    private $brand;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Collector", inversedBy="orders")
     */
    private $collector;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CollectorStatus", inversedBy="orders")
     */
    private $collectorStatus;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isExportedToCollector = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isExportedToAnotherCollector = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OrderGroupExportForCollector", inversedBy="orders")
     */
    private $exportForCollectorGroup;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OrderGroupExportForCollector", inversedBy="ordersLastParticipated")
     */
    private $exportForCollectorGroupLastParticipated;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderCheck", mappedBy="orderData", orphanRemoval=true)
     */
    private $orderChecks;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $utmSource;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $utmContent;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $utmCampaign;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $utmMedium;

    /**
     * @title Is insurance
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isInsurance = false;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $settledStatus;

    /**
     * Use this:
     * true (1) - transmitted
     * null - no
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isTransmitted = false;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\OrderKmcData", mappedBy="orderData", cascade={"persist", "remove"})
     */
    private $orderKmcData;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SmsLog", mappedBy="orderData", orphanRemoval=true)
     */
    private $smsLogItems;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="smallint", nullable=true, options={"unsigned":true})
     */
    private $version = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $passwordSuccessCheckDatetime;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderEventHistory", mappedBy="orderData", orphanRemoval=true)
     */
    private $orderEventHistoryEntities;


    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isDataConfirmed = false;

    /**
     * @ORM\OneToMany(targetEntity=OrganisationBrandOrderEventHistory::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $brandOrderEventHistoryItems;

    /**
     * @title Status before
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $statusBefore;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $statusUpdateDatetime;

    /**
     * @title Has signature
     *
     * It can be:
     * yes, no, loaded
     * @ORM\Column(type="string", length=6, options={"default": "no"})
     */
    private $hasSignature = 'no';

    /**
     * @title Has selfie
     *
     * It can be:
     * yes, no, loaded
     *
     * @ORM\Column(type="string", length=6, options={"default": "no"})
     */
    private $hasSelfie = 'no';

    /**
     * @title Barcode
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $barcode = null;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isSmsConfirmedByCode = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isSmsConfirmedByHash = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isEmailConfirmed = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isSuspended = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $suspendReason;

    /**
     * @ORM\OneToMany(targetEntity=WorkflowAlfa::class, mappedBy="orderData")
     */
    private $workflows;

    /**
     * @ORM\OneToMany(targetEntity=OrderApiExportLog::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $orderApiExportLogs;

    /**
     * @ORM\ManyToOne(targetEntity=LawStatus::class, inversedBy="orders")
     */
    private $lawStatus;

    /**
     * @ORM\ManyToOne(targetEntity=CollectorClientBrand::class, inversedBy="orders")
     */
    private $collectorClientBrand;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $payments;

    /**
     * @title Order uniq hash. It is 32 symbol string.
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $hash;

    /**
     * @ORM\OneToMany(targetEntity=OrderDispatchCampaignItem::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $orderDispatchCampaignItems;

    /**
     * @ORM\OneToMany(targetEntity=OrderUpdateLog::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $orderUpdateLogs;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true, "default": 0})
     */
    private $noticeCount = 0;

    /**
     * @ORM\OneToMany(targetEntity=OrderLimitEventLog::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $orderLimitEventLogs;

    /**
     * @ORM\OneToMany(targetEntity=OrderPaymentRequest::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $orderPaymentRequests;

    /**
     * @ORM\OneToMany(targetEntity=OrderPfsAccount::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $orderPfsAccounts;

    /**
     * @ORM\OneToMany(targetEntity=SendEmailWithDelay::class, mappedBy="orderData")
     */
    private $sendEmailWithDelays;

    /**
     * @ORM\ManyToOne(targetEntity=OrderCardClientCompany::class, inversedBy="orders")
     */
    private $cardClientCompany;

    /**
     * @ORM\OneToMany(targetEntity=UserFileOrderLink::class, mappedBy="orderData", orphanRemoval=true)
     */
    private $userFileOrderLinks;

    /**
     * @ORM\OneToOne(targetEntity=OrderShipping::class, mappedBy="orderData", orphanRemoval=true, cascade={"persist"})
     */
    private $shipping;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sendInvoiceDatetime;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
     */
    private $sourceCopyOrderId;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\OrderExternalData", mappedBy="orderData", cascade={"persist", "remove"})
     */
    private $externalData;

    /**
     * @ORM\OneToMany(targetEntity=OrderInvoice::class, mappedBy="orderData")
     */
    private $orderInvoice;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->properties = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->stateHistory = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->workflowOrders = new ArrayCollection();
        $this->orderEmailsToCustomer = new ArrayCollection();
        $this->notices = new ArrayCollection();
        $this->orderChecks = new ArrayCollection();
        $this->orderSmsToCustomer = new ArrayCollection();
        $this->smsLogItems = new ArrayCollection();
        $this->orderEventHistoryEntities = new ArrayCollection();
        $this->brandOrderEventHistoryItems = new ArrayCollection();
        $this->workflows = new ArrayCollection();
        $this->orderApiExportLogs = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->orderDispatchCampaignItems = new ArrayCollection();
        $this->orderUpdateLogs = new ArrayCollection();
        $this->orderLimitEventLogs = new ArrayCollection();
        $this->orderPaymentRequests = new ArrayCollection();
        $this->orderPfsAccounts = new ArrayCollection();
        $this->sendEmailWithDelays = new ArrayCollection();
        $this->userFileOrderLinks = new ArrayCollection();
        $this->orderInvoice = new ArrayCollection();
    }

    public function getFirstId()
    {
        if ($id = $this->getOrderId()) {
            return $id;
        }
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCustomerData(): ?OrderCustomerData
    {
        return $this->customerData;
    }

    public function setCustomerData(?OrderCustomerData $customerData): self
    {
        $this->customerData = $customerData;

        return $this;
    }

    public function getDatetimeCreate(): ?\DateTimeInterface
    {
        return $this->datetimeCreate;
    }

    public function setDatetimeCreate(\DateTimeInterface $datetimeCreate): self
    {
        $this->datetimeCreate = $datetimeCreate;

        return $this;
    }

    public function getDatetimeUpdate(): ?\DateTimeInterface
    {
        return $this->datetimeUpdate;
    }

    public function setDatetimeUpdate(?\DateTimeInterface $datetimeUpdate): self
    {
        $this->datetimeUpdate = $datetimeUpdate;

        return $this;
    }

    public function getDatetimeExternal(): ?\DateTimeInterface
    {
        return $this->datetimeExternal;
    }

    public function setDatetimeExternal(?\DateTimeInterface $datetimeExternal): self
    {
        $this->datetimeExternal = $datetimeExternal;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection|OrderItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
            // set the owning side to null (unless already changed)
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderProperty[]
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * @return OrderProperty[]
     */
    public function getPropertiesNameKey()
    {
        if ($this->propertiesNameKey) {
            return $this->propertiesNameKey;
        }
        $properties = $this->getProperties();
        $this->propertiesNameKey = [];
        foreach ($properties as $entity) {
            $this->propertiesNameKey[$entity->getName()] = $entity;
        }
        return $this->propertiesNameKey;

    }

    public function set($name, $value): self
    {
        $properties = $this->getPropertiesNameKey();
        if (array_key_exists($name, $properties)) {
            $properties[$name]->setValue($value);
        } else {
            $properties[$name] = new OrderProperty();
            $properties[$name]->setName($name)
                ->setValue($value);
            $this->addProperty($properties[$name]);
        }
        return $this;
    }

    public function get($name)
    {
        $properties = $this->getPropertiesNameKey();
        if (array_key_exists($name, $properties)) {
            return $properties[$name];
        }
        return null;
    }

    public function addProperty(OrderProperty $property): self
    {
        if (!$this->properties->contains($property)) {
            $this->properties[] = $property;
            $property->setOrder($this);
        }

        return $this;
    }

    public function removeProperty(OrderProperty $property): self
    {
        if ($this->properties->contains($property)) {
            $this->properties->removeElement($property);
            // set the owning side to null (unless already changed)
            if ($property->getOrder() === $this) {
                $property->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderTransaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(OrderTransaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setOrder($this);
        }

        return $this;
    }

    public function removeTransaction(OrderTransaction $transaction): self
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getOrder() === $this) {
                $transaction->setOrder(null);
            }
        }

        return $this;
    }

    public function getWebSite(): ?OrganisationWebSite
    {
        return $this->webSite;
    }

    public function setWebSite(?OrganisationWebSite $webSite): self
    {
        $this->webSite = $webSite;

        return $this;
    }

    public function getDebt(): ?string
    {
        return $this->debt;
    }

    public function setDebt(string $debt): self
    {
        $this->debt = $debt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function doStuffOnPrePersist()
    {
        $this->datetimeCreate = new \DateTime('now');
        $this->hash = md5(uniqid());
    }

    /**
     * @ORM\PreUpdate
     */
    public function doStuffOnPreUpdate()
    {
        $this->datetimeUpdate = new \DateTime('now');
    }

    /**
     * @param null $key
     * @return array|mixed|null
     */
    public function getMeta($key = null)
    {
        if ($key) {
            return $this->meta[$key] ?? null;
        }
        return $this->meta;
    }

    public function setMeta(?array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function addToMeta($key, $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * @return Collection|OrderStateHistory[]
     */
    public function getStateHistory(): Collection
    {
        return $this->stateHistory;
    }

    public function addStateHistory(OrderStateHistory $stateHistory): self
    {
        if (!$this->stateHistory->contains($stateHistory)) {
            $this->stateHistory[] = $stateHistory;
            $stateHistory->setOrderData($this);
        }

        return $this;
    }

    public function removeStateHistory(OrderStateHistory $stateHistory): self
    {
        if ($this->stateHistory->contains($stateHistory)) {
            $this->stateHistory->removeElement($stateHistory);
            // set the owning side to null (unless already changed)
            if ($stateHistory->getOrderData() === $this) {
                $stateHistory->setOrderData(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderFile[]
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(OrderFile $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files[] = $file;
            $file->setOrderData($this);
        }

        return $this;
    }

    public function removeFile(OrderFile $file): self
    {
        if ($this->files->contains($file)) {
            $this->files->removeElement($file);
            // set the owning side to null (unless already changed)
            if ($file->getOrderData() === $this) {
                $file->setOrderData(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|WorkflowOrder[]
     */
    public function getWorkflowOrders(): Collection
    {
        return $this->workflowOrders;
    }

    public function addWorkflowOrder(WorkflowOrder $workflowOrder): self
    {
        if (!$this->workflowOrders->contains($workflowOrder)) {
            $this->workflowOrders[] = $workflowOrder;
            $workflowOrder->setOrderData($this);
        }

        return $this;
    }

    public function removeWorkflowOrder(WorkflowOrder $workflowOrder): self
    {
        if ($this->workflowOrders->contains($workflowOrder)) {
            $this->workflowOrders->removeElement($workflowOrder);
            // set the owning side to null (unless already changed)
            if ($workflowOrder->getOrderData() === $this) {
                $workflowOrder->setOrderData(null);
            }
        }

        return $this;
    }

    public function getStatusPublic(): ?string
    {
        return $this->statusPublic;
    }

    public function setStatusPublic(?string $statusPublic): self
    {
        if ($statusPublic != $this->statusPublic) {
            $this->statusUpdateDatetime = new \DateTime('now');
            $this->statusBefore = $this->statusPublic;
        }

        $this->statusPublic = $statusPublic;

        return $this;
    }

    /**
     * @return Collection|OrderEmailToCustomer[]
     */
    public function getOrderEmailsToCustomer(): Collection
    {
        return $this->orderEmailsToCustomer;
    }

    public function addOrderEmailsToCustomer(OrderEmailToCustomer $orderEmailsToCustomer): self
    {
        if (!$this->orderEmailsToCustomer->contains($orderEmailsToCustomer)) {
            $this->orderEmailsToCustomer[] = $orderEmailsToCustomer;
            $orderEmailsToCustomer->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderEmailsToCustomer(OrderEmailToCustomer $orderEmailsToCustomer): self
    {
        if ($this->orderEmailsToCustomer->contains($orderEmailsToCustomer)) {
            $this->orderEmailsToCustomer->removeElement($orderEmailsToCustomer);
            // set the owning side to null (unless already changed)
            if ($orderEmailsToCustomer->getOrderData() === $this) {
                $orderEmailsToCustomer->setOrderData(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderNotice[]
     */
    public function getNotices(): Collection
    {
        return $this->notices;
    }

    public function addNotice(OrderNotice $notice): self
    {
        if (!$this->notices->contains($notice)) {
            $this->notices[] = $notice;
            $notice->setOrderData($this);
        }

        return $this;
    }

    public function removeNotice(OrderNotice $notice): self
    {
        if ($this->notices->contains($notice)) {
            $this->notices->removeElement($notice);
            // set the owning side to null (unless already changed)
            if ($notice->getOrderData() === $this) {
                $notice->setOrderData(null);
            }
        }

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCookie(): ?array
    {
        return $this->cookie;
    }

    public function setCookie(?array $Cookie): self
    {
        $this->cookie = $Cookie;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSourceCode(): ?string
    {
        return $this->sourceCode;
    }

    public function setSourceCode(?string $sourceCode): self
    {
        $this->sourceCode = $sourceCode;

        return $this;
    }

    public function getDatetimeImport(): ?\DateTimeInterface
    {
        return $this->datetimeImport;
    }

    public function setDatetimeImport(?\DateTimeInterface $datetimeImport): self
    {
        $this->datetimeImport = $datetimeImport;

        return $this;
    }

    public function getCircumstances(): ?array
    {
        return $this->circumstances;
    }

    public function setCircumstances(?array $circumstances): self
    {
        $this->circumstances = $circumstances;

        return $this;
    }

    public function addCircumstance($name, $value): self
    {
        $this->circumstances[$name] = $value;

        return $this;
    }

    public function getSourceOrderId(): ?string
    {
        return $this->sourceOrderId;
    }

    public function setSourceOrderId(?string $sourceOrderId): self
    {
        $this->sourceOrderId = $sourceOrderId;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getCreditRequestInfo(): ?array
    {
        $data = $this->creditRequestInfo;
        if ($this->capitaliseFirstLetter and is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, ['nationality', 'employer_country', 'iban'])) {
                    $data[$key] = $value;
                } else {
                    $data[$key] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
                }
            }
        }
        return $data;
    }

    public function setCreditRequestInfo(?array $creditRequestInfo): self
    {
        if (is_array($creditRequestInfo) and $creditRequestInfo) {
            if (array_key_exists('nationality', $creditRequestInfo)) {
                $creditRequestInfo['nationality'] = $this->countryCodeRename($creditRequestInfo['nationality']);
            }
            if (array_key_exists('employer_country', $creditRequestInfo)) {
            //if ($value = $creditRequestInfo['employer_country'] ?? false) {
                $creditRequestInfo['employer_country'] = $this->countryCodeRename($creditRequestInfo['employer_country']);
            }
        }
        $this->creditRequestInfo = $creditRequestInfo;

        return $this;
    }

    public function getBrand(): ?OrganisationBrand
    {
        return $this->brand;
    }

    public function setBrand(?OrganisationBrand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getCollector(): ?Collector
    {
        return $this->collector;
    }

    public function setCollector(?Collector $collector): self
    {
        $this->collector = $collector;

        return $this;
    }

    public function getCollectorStatus(): ?CollectorStatus
    {
        return $this->collectorStatus;
    }

    public function setCollectorStatus(?CollectorStatus $collectorStatus = null): self
    {
        $this->collectorStatus = $collectorStatus;

        return $this;
    }

    public function getExportForCollectorGroup(): ?OrderGroupExportForCollector
    {
        return $this->exportForCollectorGroup;
    }

    public function setExportForCollectorGroup(?OrderGroupExportForCollector $exportForCollectorGroup): self
    {
        $this->exportForCollectorGroup = $exportForCollectorGroup;

        return $this;
    }

    /**
     * @return Collection|OrderCheck[]
     */
    public function getOrderChecks(): Collection
    {
        return $this->orderChecks;
    }

    public function addOrderCheck(OrderCheck $orderCheck): self
    {
        if (!$this->orderChecks->contains($orderCheck)) {
            $this->orderChecks[] = $orderCheck;
            $orderCheck->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderCheck(OrderCheck $orderCheck): self
    {
        if ($this->orderChecks->contains($orderCheck)) {
            $this->orderChecks->removeElement($orderCheck);
            // set the owning side to null (unless already changed)
            if ($orderCheck->getOrderData() === $this) {
                $orderCheck->setOrderData(null);
            }
        }

        return $this;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function setUtmSource(?string $utmSource): self
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    public function getUtmContent(): ?string
    {
        return $this->utmContent;
    }

    public function setUtmContent(?string $utmContent): self
    {
        $this->utmContent = $utmContent;

        return $this;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): self
    {
        $this->utmCampaign = $utmCampaign;

        return $this;
    }

    public function getExportForCollectorGroupLastParticipated(): ?OrderGroupExportForCollector
    {
        return $this->exportForCollectorGroupLastParticipated;
    }

    public function setExportForCollectorGroupLastParticipated(?OrderGroupExportForCollector $exportForCollectorGroupLastParticipated): self
    {
        $this->exportForCollectorGroupLastParticipated = $exportForCollectorGroupLastParticipated;

        return $this;
    }

    /**
     * @return Collection|OrderSmsToCustomer[]
     */
    public function getOrderSmsToCustomer(): Collection
    {
        return $this->orderSmsToCustomer;
    }

    public function addOrderSmsToCustomer(OrderSmsToCustomer $orderSmsToCustomer): self
    {
        if (!$this->orderSmsToCustomer->contains($orderSmsToCustomer)) {
            $this->orderSmsToCustomer[] = $orderSmsToCustomer;
            $orderSmsToCustomer->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderSmsToCustomer(OrderSmsToCustomer $orderSmsToCustomer): self
    {
        if ($this->orderSmsToCustomer->contains($orderSmsToCustomer)) {
            $this->orderSmsToCustomer->removeElement($orderSmsToCustomer);
            // set the owning side to null (unless already changed)
            if ($orderSmsToCustomer->getOrderData() === $this) {
                $orderSmsToCustomer->setOrderData(null);
            }
        }

        return $this;
    }

    public function getIsInsurance(): ?bool
    {
        return $this->isInsurance;
    }

    public function setIsInsurance(?bool $isInsurance): self
    {
        $this->isInsurance = $isInsurance;

        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): self
    {
        $this->utmMedium = $utmMedium;

        return $this;
    }

    public function getSettledStatus(): ?string
    {
        return $this->settledStatus;
    }

    public function setSettledStatus(?string $settledStatus): self
    {
        $this->settledStatus = $settledStatus;

        return $this;
    }

    public function getIsTransmitted(): bool
    {
        return $this->isTransmitted;
    }

    public function setIsTransmitted(bool $isTransmitted): self
    {
        $this->isTransmitted = $isTransmitted;

        return $this;
    }

    public function getOrderKmcData(): ?OrderKmcData
    {
        return $this->orderKmcData;
    }

    public function setOrderKmcData(OrderKmcData $orderKmcData): self
    {
        $this->orderKmcData = $orderKmcData;

        // set the owning side of the relation if necessary
        if ($orderKmcData->getOrderData() !== $this) {
            $orderKmcData->setOrderData($this);
        }

        return $this;
    }

    public function getShipping(): ?OrderShipping
    {
        return $this->shipping;
    }

    public function setShipping(OrderShipping $shipping): self
    {
        $this->shipping = $shipping;
        $shipping->setOrderData($this);

        return $this;
    }

    /**
     * @return Collection|SmsLog[]
     */
    public function getSmsLogItems(): Collection
    {
        return $this->smsLogItems;
    }

    public function addSmsLogItem(SmsLog $smsLogItem): self
    {
        if (!$this->smsLogItems->contains($smsLogItem)) {
            $this->smsLogItems[] = $smsLogItem;
            $smsLogItem->setOrderData($this);
        }

        return $this;
    }

    public function removeSmsLogItem(SmsLog $smsLogItem): self
    {
        if ($this->smsLogItems->contains($smsLogItem)) {
            $this->smsLogItems->removeElement($smsLogItem);
            // set the owning side to null (unless already changed)
            if ($smsLogItem->getOrderData() === $this) {
                $smsLogItem->setOrderData(null);
            }
        }

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getPasswordSuccessCheckDatetime(): ?\DateTimeInterface
    {
        return $this->passwordSuccessCheckDatetime;
    }

    public function setPasswordSuccessCheckDatetime(?\DateTimeInterface $passwordSuccessCheckDatetime): self
    {
        $this->passwordSuccessCheckDatetime = $passwordSuccessCheckDatetime;

        return $this;
    }

    /**
     * @return Collection|OrderEventHistory[]
     */
    public function getOrderEventHistoryEntities(): Collection
    {
        return $this->orderEventHistoryEntities;
    }

    public function addOrderEventHistoryEntity(OrderEventHistory $orderEventHistoryEntity): self
    {
        if (!$this->orderEventHistoryEntities->contains($orderEventHistoryEntity)) {
            $this->orderEventHistoryEntities[] = $orderEventHistoryEntity;
            $orderEventHistoryEntity->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderEventHistoryEntity(OrderEventHistory $orderEventHistoryEntity): self
    {
        if ($this->orderEventHistoryEntities->contains($orderEventHistoryEntity)) {
            $this->orderEventHistoryEntities->removeElement($orderEventHistoryEntity);
            // set the owning side to null (unless already changed)
            if ($orderEventHistoryEntity->getOrderData() === $this) {
                $orderEventHistoryEntity->setOrderData(null);
            }
        }

        return $this;
    }

    public function getIsExportedToCollector(): ?bool
    {
        return $this->isExportedToCollector;
    }

    public function setIsExportedToCollector(?bool $isExportedToCollector): self
    {
        $this->isExportedToCollector = $isExportedToCollector;

        return $this;
    }

    public function getIsDataConfirmed(): ?bool
    {
        return $this->isDataConfirmed;
    }

    public function setIsDataConfirmed(bool $isDataConfirmed): self
    {
        $this->isDataConfirmed = $isDataConfirmed;

        return $this;
    }

    /**
     * @return Collection|OrganisationBrandOrderEventHistory[]
     */
    public function getBrandOrderEventHistoryItems(): Collection
    {
        return $this->brandOrderEventHistoryItems;
    }

    public function addBrandOrderEventHistoryItem(OrganisationBrandOrderEventHistory $brandOrderEventHistoryItem): self
    {
        if (!$this->brandOrderEventHistoryItems->contains($brandOrderEventHistoryItem)) {
            $this->brandOrderEventHistoryItems[] = $brandOrderEventHistoryItem;
            $brandOrderEventHistoryItem->setOrderData($this);
        }

        return $this;
    }

    public function removeBrandOrderEventHistoryItem(OrganisationBrandOrderEventHistory $brandOrderEventHistoryItem): self
    {
        if ($this->brandOrderEventHistoryItems->contains($brandOrderEventHistoryItem)) {
            $this->brandOrderEventHistoryItems->removeElement($brandOrderEventHistoryItem);
            // set the owning side to null (unless already changed)
            if ($brandOrderEventHistoryItem->getOrderData() === $this) {
                $brandOrderEventHistoryItem->setOrderData(null);
            }
        }

        return $this;
    }

    public function getStatusBefore(): ?string
    {
        return $this->statusBefore;
    }

    public function setStatusBefore(?string $statusBefore): self
    {
        $this->statusBefore = $statusBefore;

        return $this;
    }

    public function getStatusUpdateDatetime(): ?\DateTimeInterface
    {
        return $this->statusUpdateDatetime;
    }

    public function setStatusUpdateDatetime(?\DateTimeInterface $statusUpdateDatetime): self
    {
        $this->statusUpdateDatetime = $statusUpdateDatetime;

        return $this;
    }

    public function getHasSignature(): ?string
    {
        return $this->hasSignature;
    }

    public function setHasSignature(string $hasSignature): self
    {
        if (strlen((string)$hasSignature) > 6) {
            $hasSignature = 'no';
        }

        $this->hasSignature = $hasSignature;

        return $this;
    }

    public function getHasSelfie(): ?string
    {
        return $this->hasSelfie;
    }

    public function setHasSelfie(string $hasSelfie): self
    {
        if (strlen((string)$hasSelfie) > 6) {
            $hasSelfie = 'no';
        }

        $this->hasSelfie = $hasSelfie;

        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(?string $barcode): self
    {
        if ($barcode and strlen((string)$barcode) > 128) {
            $barcode = null;
        }

        $this->barcode = $barcode;

        return $this;
    }

    public function getIsSmsConfirmedByCode(): ?bool
    {
        return $this->isSmsConfirmedByCode;
    }

    public function setIsSmsConfirmedByCode(bool $isSmsConfirmedByCode): self
    {
        $this->isSmsConfirmedByCode = $isSmsConfirmedByCode;

        return $this;
    }

    public function getIsSmsConfirmedByHash(): ?bool
    {
        return $this->isSmsConfirmedByHash;
    }

    public function setIsSmsConfirmedByHash(bool $isSmsConfirmedByHash): self
    {
        $this->isSmsConfirmedByHash = $isSmsConfirmedByHash;

        return $this;
    }

    public function getIsEmailConfirmed(): ?bool
    {
        return $this->isEmailConfirmed;
    }

    public function setIsEmailConfirmed(bool $isEmailConfirmed): self
    {
        $this->isEmailConfirmed = $isEmailConfirmed;

        return $this;
    }

    public function getIsSuspended(): ?bool
    {
        return $this->isSuspended;
    }

    public function setIsSuspended(bool $isSuspended): self
    {
        $this->isSuspended = $isSuspended;

        return $this;
    }

    /**
     * @return Collection|WorkflowAlfa[]
     */
    public function getWorkflows(): Collection
    {
        return $this->workflows;
    }

    public function addWorkflow(WorkflowAlfa $workflow): self
    {
        if (!$this->workflows->contains($workflow)) {
            $this->workflows[] = $workflow;
            $workflow->setOrderData($this);
        }

        return $this;
    }

    public function removeWorkflow(WorkflowAlfa $workflow): self
    {
        if ($this->workflows->contains($workflow)) {
            $this->workflows->removeElement($workflow);
            // set the owning side to null (unless already changed)
            if ($workflow->getOrderData() === $this) {
                $workflow->setOrderData(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderApiExportLog[]
     */
    public function getOrderApiExportLogs(): Collection
    {
        return $this->orderApiExportLogs;
    }

    public function addOrderApiExportLog(OrderApiExportLog $orderApiExportLog): self
    {
        if (!$this->orderApiExportLogs->contains($orderApiExportLog)) {
            $this->orderApiExportLogs[] = $orderApiExportLog;
            $orderApiExportLog->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderApiExportLog(OrderApiExportLog $orderApiExportLog): self
    {
        if ($this->orderApiExportLogs->contains($orderApiExportLog)) {
            $this->orderApiExportLogs->removeElement($orderApiExportLog);
            // set the owning side to null (unless already changed)
            if ($orderApiExportLog->getOrderData() === $this) {
                $orderApiExportLog->setOrderData(null);
            }
        }

        return $this;
    }

    public function getLawStatus(): ?LawStatus
    {
        return $this->lawStatus;
    }

    public function setLawStatus(?LawStatus $lawStatus): self
    {
        $this->lawStatus = $lawStatus;

        return $this;
    }

    public function getCollectorClientBrand(): ?CollectorClientBrand
    {
        return $this->collectorClientBrand;
    }

    public function setCollectorClientBrand(?CollectorClientBrand $collectorClientBrand): self
    {
        $this->collectorClientBrand = $collectorClientBrand;

        return $this;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setOrderData($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getOrderData() === $this) {
                $payment->setOrderData(null);
            }
        }

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return Collection|OrderDispatchCampaignItem[]
     */
    public function getOrderDispatchCampaignItems(): Collection
    {
        return $this->orderDispatchCampaignItems;
    }

    public function addOrderDispatchCampaignItem(OrderDispatchCampaignItem $orderDispatchCampaignItem): self
    {
        if (!$this->orderDispatchCampaignItems->contains($orderDispatchCampaignItem)) {
            $this->orderDispatchCampaignItems[] = $orderDispatchCampaignItem;
            $orderDispatchCampaignItem->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderDispatchCampaignItem(OrderDispatchCampaignItem $orderDispatchCampaignItem): self
    {
        if ($this->orderDispatchCampaignItems->removeElement($orderDispatchCampaignItem)) {
            // set the owning side to null (unless already changed)
            if ($orderDispatchCampaignItem->getOrderData() === $this) {
                $orderDispatchCampaignItem->setOrderData(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderUpdateLog[]
     */
    public function getOrderUpdateLogs(): Collection
    {
        return $this->orderUpdateLogs;
    }

    public function addOrderUpdateLog(OrderUpdateLog $orderUpdateLog): self
    {
        if (!$this->orderUpdateLogs->contains($orderUpdateLog)) {
            $this->orderUpdateLogs[] = $orderUpdateLog;
            $orderUpdateLog->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderUpdateLog(OrderUpdateLog $orderUpdateLog): self
    {
        if ($this->orderUpdateLogs->removeElement($orderUpdateLog)) {
            // set the owning side to null (unless already changed)
            if ($orderUpdateLog->getOrderData() === $this) {
                $orderUpdateLog->setOrderData(null);
            }
        }

        return $this;
    }

    public function getNoticeCount(): ?int
    {
        return $this->noticeCount;
    }

    public function setNoticeCount(int $noticeCount): self
    {
        $this->noticeCount = $noticeCount;

        return $this;
    }

    /**
     * @return Collection|OrderLimitEventLog[]
     */
    public function getOrderLimitEventLogs(): Collection
    {
        if ($this->orderLimitEventLogs === null) {
            $this->orderLimitEventLogs = new ArrayCollection();
        }
        return $this->orderLimitEventLogs;
    }

    public function addOrderLimitEvent(OrderLimitEventLog $orderLimitEvent): self
    {
        if (!$this->orderLimitEventLogs->contains($orderLimitEvent)) {
            $this->orderLimitEventLogs[] = $orderLimitEvent;
            $orderLimitEvent->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderLimitEventLog(OrderLimitEventLog $orderLimitEvent): self
    {
        if ($this->orderLimitEventLogs->removeElement($orderLimitEvent)) {
            // set the owning side to null (unless already changed)
            if ($orderLimitEvent->getOrderData() === $this) {
                $orderLimitEvent->setOrderData(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderPaymentRequest[]
     */
    public function getOrderPaymentRequests(): Collection
    {
        return $this->orderPaymentRequests;
    }

    public function addOrderPaymentRequest(OrderPaymentRequest $orderPaymentRequest): self
    {
        if (!$this->orderPaymentRequests->contains($orderPaymentRequest)) {
            $this->orderPaymentRequests[] = $orderPaymentRequest;
            $orderPaymentRequest->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderPaymentRequest(OrderPaymentRequest $orderPaymentRequest): self
    {
        if ($this->orderPaymentRequests->removeElement($orderPaymentRequest)) {
            // set the owning side to null (unless already changed)
            if ($orderPaymentRequest->getOrderData() === $this) {
                $orderPaymentRequest->setOrderData(null);
            }
        }

        return $this;
    }

    public function getSuspendReason(): ?string
    {
        return $this->suspendReason;
    }

    public function setSuspendReason(?string $suspendReason): self
    {
        $this->suspendReason = $suspendReason;

        return $this;
    }

    /**
     * @return Collection|OrderPfsAccount[]
     */
    public function getOrderPfsAccounts(): Collection
    {
        return $this->orderPfsAccounts;
    }

    public function addOrderPfsAccount(OrderPfsAccount $orderPfsAccount): self
    {
        if (!$this->orderPfsAccounts->contains($orderPfsAccount)) {
            $this->orderPfsAccounts[] = $orderPfsAccount;
            $orderPfsAccount->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderPfsAccount(OrderPfsAccount $orderPfsAccount): self
    {
        if ($this->orderPfsAccounts->removeElement($orderPfsAccount)) {
            // set the owning side to null (unless already changed)
            if ($orderPfsAccount->getOrderData() === $this) {
                $orderPfsAccount->setOrderData(null);
            }
        }

        return $this;
    }

    public function getFileToSendByPost(): ?string
    {
        return $this->fileToSendByPost;
    }

    public function setFileToSendByPost(string $fileToSendByPost): self
    {
        $this->fileToSendByPost = $fileToSendByPost;

        return $this;
    }

    /**
     * @return Collection|SendEmailWithDelay[]
     */
    public function getSendEmailWithDelays(): Collection
    {
        return $this->sendEmailWithDelays;
    }

    public function addSendEmailWithDelay(SendEmailWithDelay $sendEmailWithDelay): self
    {
        if (!$this->sendEmailWithDelays->contains($sendEmailWithDelay)) {
            $this->sendEmailWithDelays[] = $sendEmailWithDelay;
            $sendEmailWithDelay->setOrderData($this);
        }

        return $this;
    }

    public function removeSendEmailWithDelay(SendEmailWithDelay $sendEmailWithDelay): self
    {
        if ($this->sendEmailWithDelays->removeElement($sendEmailWithDelay)) {
            // set the owning side to null (unless already changed)
            if ($sendEmailWithDelay->getOrderData() === $this) {
                $sendEmailWithDelay->setOrderData(null);
            }
        }

        return $this;
    }

    public function getCardClientCompany(): ?OrderCardClientCompany
    {
        return $this->cardClientCompany;
    }

    public function setCardClientCompany(?OrderCardClientCompany $cardClientCompany): self
    {
        $this->cardClientCompany = $cardClientCompany;

        return $this;
    }

    public function getSendInvoiceDatetime(): ?\DateTimeInterface
    {
        return $this->sendInvoiceDatetime;
    }

    public function setSendInvoiceDatetime(?\DateTimeInterface $sendInvoiceDatetime): self
    {
        $this->sendInvoiceDatetime = $sendInvoiceDatetime;

        return $this;
    }

    /**
     * @return Collection|UserFileOrderLink[]
     */
    public function getUserFileOrderLinks(): Collection
    {
        return $this->userFileOrderLinks;
    }

    public function addUserFileOrderLink(UserFileOrderLink $userFileOrderLink): self
    {
        if (!$this->userFileOrderLinks->contains($userFileOrderLink)) {
            $this->userFileOrderLinks[] = $userFileOrderLink;
            $userFileOrderLink->setOrderData($this);
        }

        return $this;
    }

    public function removeUserFileOrderLink(UserFileOrderLink $userFileOrderLink): self
    {
        if ($this->userFileOrderLinks->removeElement($userFileOrderLink)) {
            // set the owning side to null (unless already changed)
            if ($userFileOrderLink->getOrderData() === $this) {
                $userFileOrderLink->setOrderData(null);
            }
        }

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }

    public function getIsExportedToAnotherCollector(): ?bool
    {
        return $this->isExportedToAnotherCollector;
    }

    public function setIsExportedToAnotherCollector(bool $isExportedToAnotherCollector): self
    {
        $this->isExportedToAnotherCollector = $isExportedToAnotherCollector;

        return $this;
    }

    public function getSourceCopyOrderId(): ?int
    {
        return $this->sourceCopyOrderId;
    }

    public function setSourceCopyOrderId(?int $sourceCopyOrderId): self
    {
        $this->sourceCopyOrderId = $sourceCopyOrderId;

        return $this;
    }

    public function getExternalData(): ?OrderExternalData
    {
        return $this->externalData;
    }

    public function setExternalData(OrderExternalData $externalData): self
    {
        $this->externalData = $externalData;
        if ($externalData->getOrderData() !== $this) {
            $externalData->setOrderData($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, OrderInvoice>
     */
    public function getOrderInvoice(): Collection
    {
        return $this->orderInvoice;
    }

    public function addOrderInvoice(OrderInvoice $orderInvoice): self
    {
        if (!$this->orderInvoice->contains($orderInvoice)) {
            $this->orderInvoice[] = $orderInvoice;
            $orderInvoice->setOrderData($this);
        }

        return $this;
    }

    public function removeOrderInvoice(OrderInvoice $orderInvoice): self
    {
        if ($this->orderInvoice->removeElement($orderInvoice)) {
            // set the owning side to null (unless already changed)
            if ($orderInvoice->getOrderData() === $this) {
                $orderInvoice->setOrderData(null);
            }
        }

        return $this;
    }
}
