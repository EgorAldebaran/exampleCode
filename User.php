<?php

class User implements UserInterface, PreFlushActionInterface
{
    const STATUS_TECH_ADMIN = 'tech_admin';
    const STATUS_REGULAR = 'regular';
    const STATUS_OFF = 'off';
    const STATUS_DELETED = 'deleted';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned":true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $nameSecond;

    /**
     * @ORM\Column(type="string", length=24)
     */
    private $status = 'regular';

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datetimeLogin;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datetimeCreate = null;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderStateHistory", mappedBy="user", orphanRemoval=true)
     */
    private $stateHistory;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserTemporaryValue", mappedBy="user", orphanRemoval=true)
     */
    private $temporaryValues;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserMessage", mappedBy="user", orphanRemoval=true)
     */
    private $userMessages;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserTask", mappedBy="user", orphanRemoval=true)
     */
    private $tasks;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Organisation", mappedBy="users")
     */
    private $organisations;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\OrganisationBrand", mappedBy="users")
     */
    private $organisationBrands;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderNotice", mappedBy="user")
     */
    private $orderNotices;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserFormDataHistory", mappedBy="user", orphanRemoval=true)
     */
    private $formDataHistory;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CustomerEmailChangeHistory", mappedBy="user")
     */
    private $customerEmailChangeHistoryItems;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderGroupExportForCollector", mappedBy="user")
     */
    private $orderGroupExportForCollectors;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserRelation", mappedBy="user", orphanRemoval=true)
     */
    private $relations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserFile", mappedBy="user", orphanRemoval=true)
     */
    private $userFiles;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderEventHistory", mappedBy="user")
     */
    private $orderEventHistoryEntities;

    /**
     * @ORM\OneToMany(targetEntity=UserEmailCheck::class, mappedBy="user", orphanRemoval=true)
     */
    private $userEmailChecks;

    /**
     * @ORM\OneToMany(targetEntity=OrderTransactionBankImportFile::class, mappedBy="user")
     */
    private $orderTransactionBankImportFiles;

    public function __construct()
    {
        $this->temporaryValues = new ArrayCollection();
        $this->userMessages = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->organisations = new ArrayCollection();
        $this->organisationBrands = new ArrayCollection();
        $this->orderNotices = new ArrayCollection();
        $this->formDataHistory = new ArrayCollection();
        $this->customerEmailChangeHistoryItems = new ArrayCollection();
        $this->orderGroupExportForCollectors = new ArrayCollection();
        $this->relations = new ArrayCollection();
        $this->userFiles = new ArrayCollection();
        $this->orderEventHistoryEntities = new ArrayCollection();
        $this->userEmailChecks = new ArrayCollection();
        $this->orderTransactionBankImportFiles = new ArrayCollection();
        $this->stateHistory = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @return bool
     */
    public function isAgent()
    {
        if (in_array(Roles::ROLE_AGENT, $this->getRoles())) {
            return true;
        }
        return false;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array|string $roles
     * @return $this
     */
    public function setRoles($roles): self
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return $this
     */
    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNameSecond()
    {
        return $this->nameSecond;
    }

    /**
     * @param mixed $nameSecond
     * @return $this
     */
    public function setNameSecond($nameSecond): self
    {
        $this->nameSecond = $nameSecond;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return $this
     */
    public function setStatus($status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDatetimeLogin()
    {
        return $this->datetimeLogin;
    }

    /**
     * @param mixed $datetimeLogin
     * @return $this
     */
    public function setDatetimeLogin($datetimeLogin = null): self
    {
        if (!$datetimeLogin) {
            $datetimeLogin = new \DateTime('now');
        }
        $this->datetimeLogin = $datetimeLogin;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDatetimeCreate()
    {
        return $this->datetimeCreate;
    }

    /**
     * @param mixed $datetimeCreate
     * @return $this
     */
    public function setDatetimeCreate($datetimeCreate = null): self
    {
        if (!$datetimeCreate) {
            $datetimeCreate = new \DateTime('now');
        }
        $this->datetimeCreate = $datetimeCreate;
        return $this;
    }


    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|UserTemporaryValue[]
     */
    public function getTemporaryValues(): Collection
    {
        return $this->temporaryValues;
    }

    public function addTemporaryValue(UserTemporaryValue $temporaryValue): self
    {
        if (!$this->temporaryValues->contains($temporaryValue)) {
            $this->temporaryValues[] = $temporaryValue;
            $temporaryValue->setUId($this);
        }

        return $this;
    }

    public function removeTemporaryValue(UserTemporaryValue $temporaryValue): self
    {
        if ($this->temporaryValues->contains($temporaryValue)) {
            $this->temporaryValues->removeElement($temporaryValue);
            // set the owning side to null (unless already changed)
            if ($temporaryValue->getUId() === $this) {
                $temporaryValue->setUId(null);
            }
        }

        return $this;
    }

    public function preFlush()
    {
        if (!$this->datetimeCreate) {
            $this->datetimeCreate = new \DateTime('now');
        }
    }

    /**
     * @return Collection|UserMessage[]
     */
    public function getUserMessages(): Collection
    {
        return $this->userMessages;
    }

    public function addUserMessage(UserMessage $userMessage): self
    {
        if (!$this->userMessages->contains($userMessage)) {
            $this->userMessages[] = $userMessage;
            $userMessage->setUser($this);
        }

        return $this;
    }

    public function removeUserMessage(UserMessage $userMessage): self
    {
        if ($this->userMessages->contains($userMessage)) {
            $this->userMessages->removeElement($userMessage);
            // set the owning side to null (unless already changed)
            if ($userMessage->getUser() === $this) {
                $userMessage->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserTask[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(UserTask $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setUser($this);
        }

        return $this;
    }

    public function removeTask(UserTask $task): self
    {
        if ($this->tasks->contains($task)) {
            $this->tasks->removeElement($task);
            // set the owning side to null (unless already changed)
            if ($task->getUser() === $this) {
                $task->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Organisation[]
     */
    public function getOrganisations(): Collection
    {
        return $this->organisations;
    }

    public function addOrganisation(Organisation $organisation): self
    {
        if (!$this->organisations->contains($organisation)) {
            $this->organisations[] = $organisation;
            $organisation->addUser($this);
        }

        return $this;
    }

    public function removeOrganisation(Organisation $organisation): self
    {
        if ($this->organisations->contains($organisation)) {
            $this->organisations->removeElement($organisation);
            $organisation->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|OrganisationBrand[]
     */
    public function getOrganisationBrands(): Collection
    {
        return $this->organisationBrands;
    }

    public function addOrganisationBrand(OrganisationBrand $organisationBrand): self
    {
        if (!$this->organisationBrands->contains($organisationBrand)) {
            $this->organisationBrands[] = $organisationBrand;
            $organisationBrand->addUser($this);
        }

        return $this;
    }

    public function removeOrganisationBrand(OrganisationBrand $organisationBrand): self
    {
        if ($this->organisationBrands->contains($organisationBrand)) {
            $this->organisationBrands->removeElement($organisationBrand);
            $organisationBrand->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|OrderNotice[]
     */
    public function getOrderNotices(): Collection
    {
        return $this->orderNotices;
    }

    public function addOrderNotice(OrderNotice $orderNotice): self
    {
        if (!$this->orderNotices->contains($orderNotice)) {
            $this->orderNotices[] = $orderNotice;
            $orderNotice->setUser($this);
        }

        return $this;
    }

    public function removeOrderNotice(OrderNotice $orderNotice): self
    {
        if ($this->orderNotices->contains($orderNotice)) {
            $this->orderNotices->removeElement($orderNotice);
            // set the owning side to null (unless already changed)
            if ($orderNotice->getUser() === $this) {
                $orderNotice->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserFormDataHistory[]
     */
    public function getFormDataHistory(): Collection
    {
        return $this->formDataHistory;
    }

    public function addFormDataHistory(UserFormDataHistory $formDataHistory): self
    {
        if (!$this->formDataHistory->contains($formDataHistory)) {
            $this->formDataHistory[] = $formDataHistory;
            $formDataHistory->setUser($this);
        }

        return $this;
    }

    public function removeFormDataHistory(UserFormDataHistory $formDataHistory): self
    {
        if ($this->formDataHistory->contains($formDataHistory)) {
            $this->formDataHistory->removeElement($formDataHistory);
            // set the owning side to null (unless already changed)
            if ($formDataHistory->getUser() === $this) {
                $formDataHistory->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CustomerEmailChangeHistory[]
     */
    public function getCustomerEmailChangeHistoryItems(): Collection
    {
        return $this->customerEmailChangeHistoryItems;
    }

    public function addCustomerEmailChangeHistoryItem(CustomerEmailChangeHistory $customerEmailChangeHistoryItem): self
    {
        if (!$this->customerEmailChangeHistoryItems->contains($customerEmailChangeHistoryItem)) {
            $this->customerEmailChangeHistoryItems[] = $customerEmailChangeHistoryItem;
            $customerEmailChangeHistoryItem->setUser($this);
        }

        return $this;
    }

    public function removeCustomerEmailChangeHistoryItem(CustomerEmailChangeHistory $customerEmailChangeHistoryItem): self
    {
        if ($this->customerEmailChangeHistoryItems->contains($customerEmailChangeHistoryItem)) {
            $this->customerEmailChangeHistoryItems->removeElement($customerEmailChangeHistoryItem);
            // set the owning side to null (unless already changed)
            if ($customerEmailChangeHistoryItem->getUser() === $this) {
                $customerEmailChangeHistoryItem->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderGroupExportForCollector[]
     */
    public function getOrderGroupExportForCollectors(): Collection
    {
        return $this->orderGroupExportForCollectors;
    }

    public function addOrderGroupExportForCollector(OrderGroupExportForCollector $orderGroupExportForCollector): self
    {
        if (!$this->orderGroupExportForCollectors->contains($orderGroupExportForCollector)) {
            $this->orderGroupExportForCollectors[] = $orderGroupExportForCollector;
            $orderGroupExportForCollector->setUser($this);
        }

        return $this;
    }

    public function removeOrderGroupExportForCollector(OrderGroupExportForCollector $orderGroupExportForCollector): self
    {
        if ($this->orderGroupExportForCollectors->contains($orderGroupExportForCollector)) {
            $this->orderGroupExportForCollectors->removeElement($orderGroupExportForCollector);
            // set the owning side to null (unless already changed)
            if ($orderGroupExportForCollector->getUser() === $this) {
                $orderGroupExportForCollector->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserRelation[]
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function addRelation(UserRelation $relation): self
    {
        if (!$this->relations->contains($relation)) {
            $this->relations[] = $relation;
            $relation->setUser($this);
        }

        return $this;
    }

    public function removeRelation(UserRelation $relation): self
    {
        if ($this->relations->contains($relation)) {
            $this->relations->removeElement($relation);
            // set the owning side to null (unless already changed)
            if ($relation->getUser() === $this) {
                $relation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserFile[]
     */
    public function getUserFiles(): Collection
    {
        return $this->userFiles;
    }

    public function addUserFile(UserFile $userFile): self
    {
        if (!$this->userFiles->contains($userFile)) {
            $this->userFiles[] = $userFile;
            $userFile->setUser($this);
        }

        return $this;
    }

    public function removeUserFile(UserFile $userFile): self
    {
        if ($this->userFiles->contains($userFile)) {
            $this->userFiles->removeElement($userFile);
            // set the owning side to null (unless already changed)
            if ($userFile->getUser() === $this) {
                $userFile->setUser(null);
            }
        }

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
            $orderEventHistoryEntity->setUser($this);
        }

        return $this;
    }

    public function removeOrderEventHistoryEntity(OrderEventHistory $orderEventHistoryEntity): self
    {
        if ($this->orderEventHistoryEntities->contains($orderEventHistoryEntity)) {
            $this->orderEventHistoryEntities->removeElement($orderEventHistoryEntity);
            // set the owning side to null (unless already changed)
            if ($orderEventHistoryEntity->getUser() === $this) {
                $orderEventHistoryEntity->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserEmailCheck[]
     */
    public function getUserEmailChecks(): Collection
    {
        return $this->userEmailChecks;
    }

    public function addUserEmailCheck(UserEmailCheck $userEmailCheck): self
    {
        if (!$this->userEmailChecks->contains($userEmailCheck)) {
            $this->userEmailChecks[] = $userEmailCheck;
            $userEmailCheck->setUser($this);
        }

        return $this;
    }

    public function removeUserEmailCheck(UserEmailCheck $userEmailCheck): self
    {
        if ($this->userEmailChecks->contains($userEmailCheck)) {
            $this->userEmailChecks->removeElement($userEmailCheck);
            // set the owning side to null (unless already changed)
            if ($userEmailCheck->getUser() === $this) {
                $userEmailCheck->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OrderTransactionBankImportFile[]
     */
    public function getOrderTransactionBankImportFiles(): Collection
    {
        return $this->orderTransactionBankImportFiles;
    }

    public function addOrderTransactionBankImportFile(OrderTransactionBankImportFile $orderTransactionBankImportFile): self
    {
        if (!$this->orderTransactionBankImportFiles->contains($orderTransactionBankImportFile)) {
            $this->orderTransactionBankImportFiles[] = $orderTransactionBankImportFile;
            $orderTransactionBankImportFile->setUser($this);
        }

        return $this;
    }

    public function removeOrderTransactionBankImportFile(OrderTransactionBankImportFile $orderTransactionBankImportFile): self
    {
        if ($this->orderTransactionBankImportFiles->contains($orderTransactionBankImportFile)) {
            $this->orderTransactionBankImportFiles->removeElement($orderTransactionBankImportFile);
            // set the owning side to null (unless already changed)
            if ($orderTransactionBankImportFile->getUser() === $this) {
                $orderTransactionBankImportFile->setUser(null);
            }
        }

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

}
