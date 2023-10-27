<?php

namespace App\EntityAction\Order;

use App\Entity\Order;
use App\Service\Orders;
use Symfony\Component\Security\Core\Security;

class AddHistoryEntity
{

    const TYPE_COLLECTOR_EXPORT = 'collector_export';

    /**
     * @var \Symfony\Component\Security\Core\User\UserInterface|null
     */
    protected $currentUser;

    /**
     * @var Orders
     */
    protected $ordersService;


    protected $title = null;
    protected $type = null;

    private $contextType = null;
    private $contextValue = null;
    private $dataOld = [];
    private $dataNew = [];

    public function __construct(Security $security, Orders $orders)
    {
        $this->currentUser = $security->getUser();
        $this->ordersService = $orders;
    }

    public function handle(Order $order)
    {
        $instance = $this->ordersService->getStateHistoryInstance($order)
            ->setTitle($this->title)
            ->setType($this->type)
            ->setDataNew($this->dataNew)
            ->setDataOld($this->dataOld)
            ->setContextType($this->contextType)
            ->setContextValue($this->contextValue)
            ;

        if ($this->currentUser) {
            $instance->setUser($this->currentUser);
        }

        $this->ordersService->getEntityManager()->flush();
    }

    /**
     * @param null $title
     * @return $this
     */
    public function setTitle($title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param null $type
     * @return $this
     */
    public function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param null $contextType
     * @return $this
     */
    public function setContextType($contextType): self
    {
        $this->contextType = $contextType;
        return $this;
    }

    /**
     * @param null $contextValue
     * @return $this
     */
    public function setContextValue($contextValue): self
    {
        $this->contextValue = $contextValue;
        return $this;
    }

    /**
     * @param array $dataOld
     * @return $this
     */
    public function setDataOld(array $dataOld): self
    {
        $this->dataOld = $dataOld;
        return $this;
    }

    /**
     * @param array $dataNew
     * @return $this
     */
    public function setDataNew(array $dataNew): self
    {
        $this->dataNew = $dataNew;
        return $this;
    }

}
