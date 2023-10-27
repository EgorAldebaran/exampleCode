<?php

class WorkflowActivate
{
    private $errorToReady = true;

    private $stopToReady = true;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Order $order
     */
    public function handle(Order $order)
    {
        $orders = $order->getWorkflows();
        if ($orders) {
            foreach ($orders as $entity) {
                $this->handleWorkflowAlfa($entity);
                break;
            }

            $this->entityManager->flush();
            return;
        }


        $orders = $order->getWorkflowOrders();
        if (!$orders) {
            return;
        }

        foreach ($orders as $entity) {
            if ($this->errorToReady and $entity->getStatus() == WorkflowOrder::STATUS_ERROR) {
                $this->handleWorkflowErrorToReady($entity);
            } else if ($this->stopToReady and $entity->getStatus() == WorkflowOrder::STATUS_STOP) {
                $this->handleWorkflowErrorToReady($entity);
            }
            break;
        }

        $this->entityManager->flush();
    }

    private function handleWorkflowAlfa(WorkflowAlfa $entity)
    {
        if ($this->errorToReady and $entity->getStatus() == WorkflowAlfa::STATUS_ERROR) {
            $entity->setPause(false);
            $entity->setStatus(WorkflowAlfa::STATUS_READY);
        }
    }

    private function handleWorkflowErrorToReady(WorkflowOrder $entity)
    {
        $circumstances = [
            'status' => $entity->getStatus()
        ];
        $entity->setCircumstances($circumstances);
        $entity->setPause(false);
        $entity->setStatus(WorkflowOrder::STATUS_READY);
    }
}
