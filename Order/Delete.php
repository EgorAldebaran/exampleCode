<?php

class Delete
{
    /**
     * @var Orders
     */
    private $orders;

    /**
     * @var CustomerBrandService
     */
    private $customerBrandService;

    /**
     * @var OrderCustomersData
     */
    private $customersData;

    /**
     * @var Customers
     */
    private $customers;

    /**
     * @var JobQueueService
     */
    private $jobQueueService;

    /**
     * @var \App\EntityAction\OrderEmailToCustomer\Delete
     */
    private $deleteEmailAction;

    /**
     * @var \App\EntityAction\OrderFile\Delete
     */
    private $deleteFileAction;

    /**
     * @var \App\EntityAction\WorkflowOrder\Delete
     */
    private $deleteWorkflow;

    public function __construct(Orders $orders,
                                \App\EntityAction\WorkflowOrder\Delete $deleteWorkflow,
                                CustomerBrandService $customerBrandService,
                                Customers $customers,
                                \App\EntityAction\OrderEmailToCustomer\Delete $deleteEmailAction,
                                \App\EntityAction\OrderFile\Delete $deleteFileAction,
                                JobQueueService $jobQueueService,
                                OrderCustomersData $customersData)
    {
        $this->deleteFileAction = $deleteFileAction;
        $this->deleteEmailAction = $deleteEmailAction;
        $this->orders = $orders;
        $this->customers = $customers;
        $this->customerBrandService = $customerBrandService;
        $this->customersData = $customersData;
        $this->jobQueueService = $jobQueueService;
        $this->deleteWorkflow = $deleteWorkflow;
    }

    /**
     * @param Order $order
     */
    public function handle(Order $order)
    {
        $entityManager = $this->orders->getEntityManager();

        $this->jobQueueService->getRepository()->deleteForContext(JobQueue::CONTEXT_TYPE_ORDER, $order->getId());

        $order->setStatusPublic(Order::STATUS_DELETED)->setStatus(Order::STATUS_DELETED);
        $entityManager->flush();

        $emails = $order->getOrderEmailsToCustomer();
        foreach ($emails as $email) {
            if (!$this->deleteEmailAction->handle($email)) {
                return false;
            }
        }

        // files
        $list = $order->getFiles();
        foreach ($list as $entity) {
            if (!$this->deleteFileAction->handle($entity)) {
                return false;
            }
        }


        $orders = $order->getWorkflowOrders();
        foreach ($orders as $entity) {
            $this->deleteWorkflow->handle($entity);
        }

        // history
        $list = $order->getStateHistory();
        foreach ($list as $entity) {
            $order->removeStateHistory($entity);
            $entityManager->remove($entity);
        }

        $list = $order->getOrderApiExportLogs();
        foreach ($list as $entity) {
            $entityManager->remove($entity);
        }

        $list = $order->getWorkflows();
        foreach ($list as $entity) {
            $entityManager->remove($entity);
        }

        // transactions
        $list = $order->getTransactions();
        foreach ($list as $entity) {
            $order->removeTransaction($entity);
            $entityManager->remove($entity);
        }

        // properties
        $list = $order->getProperties();
        foreach ($list as $entity) {
            $order->removeProperty($entity);
            $entityManager->remove($entity);
        }

        $removeCustomer = false;
        $customerData = $order->getCustomerData();
        $customer = $customerData->getCustomer();

        // items
        $brandsIds = [];
        $list = $order->getItems();
        foreach ($list as $entity) {
            $order->removeItem($entity);
            $entityManager->remove($entity);
        }

        if ($brand = $order->getBrand()) {
            $customerBrand = $this->customerBrandService->getRepository()->findOneBy([
                'customer' => $customer,
                'brand' => $brand,
            ]);

            if ($customerBrand) {
                $customer->removeBrand($customerBrand);
                $entityManager->remove($customerBrand);
            }
        }

        $entityManager->remove($order);
        $entityManager->flush();

        $data = $this->orders->getRepository()->addFilterComparison('customerData', $customerData)->get();
        if (count($data) == 1) {
            $removeCustomer = true;
            $entityManager->remove($customerData);
            $customer->removeOrderCustomer($customerData);
        }
        $entityManager->flush();

        if (!$removeCustomer) {
            return true;
        }

        //$entityManager->clear();
        $customerBrands = $this->customerBrandService->getRepository()->addFilterComparison('customer', $customer)->get();
        $customerData = $this->customersData->getRepository()->addFilterComparison('customer', $customer)->get();

        if (count($customerBrands) == 0 and count($customerData) == 0) {
            $customer = $this->customers->getRepository()->find($customer->getId());
            $entityManager->remove($customer);
            $entityManager->flush();
        }

        return true;
    }
}
