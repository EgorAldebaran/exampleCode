<?php

class AddTransactionPercent extends AddOrderTransactionAbstract
{
    /**
     * @var null|int
     */
    private $percent = null;

    private $meta = [];

    private $status = 'common';

    private $transactionRealTime = null;

    private $description = null;

    private $bankAccount = null;


    public function handle(Order $order)
    {
        if (!$this->percent) {
            return null;
        }

        $sum = round(($order->getDebt() /100) * $this->percent, 2);

        $dataOld = null;
        $entity = $this->ordersService->getTransactionInstance();
        $clarification = UpdateBefore::CLARIFICATION_TRANSACTION_ADD;

        $entity->setOrder($order);
        //$order->addTransaction($entity);

        $entity->setTitle($this->title);
        $entity->setSum($sum);
        $entity->setStatus($this->status);
        $entity->setMeta($this->meta);

        $entity->setDescription($this->description);
        $entity->setBankAccount($this->bankAccount);
        $entity->setTransactionRealTime($this->transactionRealTime);

        $this->handleLinkedTransactionTags($entity);
        $this->ordersService->flush();

        if ($this->handleOrderPaymentRequest and $entity->getId() and $requests = $order->getOrderPaymentRequests()) {
            foreach ($requests as $requestEntity) {
                $this->addTransactionForPaymentRequestAction->setTransaction($entity)
                    ->handle($requestEntity);
            }
        }

        $this->ordersService->getEntityManager()->refresh($order);
        $event = new UpdateAfter($order);
        $event->setTransaction($entity);
        $event->setDataOld($dataOld);
        $event->setClarification($clarification);
        $this->eventDispatcher->dispatch($event);

        return $entity;
    }

    /**
     * @param int|null $percent
     * @return $this
     */
    public function setPercent($percent): self
    {
        $percent = abs((int)$percent);
        $this->percent = $percent;
        return $this;
    }


    /**
     * @param array $meta
     * @return $this
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param null $transactionRealTime
     * @return $this
     */
    public function setTransactionRealTime($transactionRealTime): self
    {
        $this->transactionRealTime = $transactionRealTime;
        return $this;
    }

    /**
     * @param null $description
     * @return $this
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param null $bankAccount
     * @return $this
     */
    public function setBankAccount($bankAccount): self
    {
        $this->bankAccount = $bankAccount;
        return $this;
    }


}
