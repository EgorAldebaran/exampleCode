<?php

class AddCheckEmailConfirmation
{
    /**
     * @var Orders
     */
    protected $ordersService;

    public function __construct(Orders $ordersService)
    {
        $this->ordersService = $ordersService;
    }

    public function handle(Order $order)
    {
        $list = $this->ordersService->getOrderCheckRepository()->getForOrderCodeKey($order);
        if (array_key_exists(OrderCheck::CODE_EMAIL_CONFIRMATION, $list)) {
            return false;
        }
        $check = $this->ordersService->getOrderCheckEntity($order, OrderCheck::CODE_EMAIL_CONFIRMATION);
        $check->setHash(md5(uniqid()));
        $this->ordersService->getEntityManager()->flush();
        return true;
    }
}
