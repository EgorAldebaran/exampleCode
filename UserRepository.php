<?php

declare(strict_types=1);

class UserRepository extends ServiceEntityRepository
{
    public const THREE_MONTHS_AGO = 3;
    public const SIX_MONTHS_AGO = 6;
    public const TWELVE_MONTHS_AGO = 12;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function get(int $id): User
    {
        $user = $this->find($id);

        if ($user === null) {
            throw new EntityNotFoundException('User is not found.');
        }

        return $user;
    }

    /**
     * @return int
     * @throws Exception
     * @throws NonUniqueResultException
     */
    public function getUserCountForYesterdayTillCurrentTime(): int
    {
        $yesterday = (new \DateTime())->sub(new \DateInterval('P1D'));

        return (int) $this->createQueryBuilder('user')
            ->select('count(user.id) as c'/** @see User::$id */)
            ->where('user.registrationDate BETWEEN :dayStart AND :tillTime'/** @see User::$registrationDate */)
            ->setParameter('dayStart', $yesterday->format('Y-m-d'))
            ->setParameter('tillTime', $yesterday->format('Y-m-d H:i:s'))
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $days
     * @return int
     * @throws Exception
     * @throws ORMException
     */
    public function getUserCountForSpecificPeriodTillTime(int $days): int
    {
        $startDay = (new \DateTime())->sub(new \DateInterval('P' . $days . 'D'));

        return (int) $this->createQueryBuilder('user')
            ->select('count(user.id) as c'/** @see User::$id */)
            ->where('user.registrationDate >= :dayStart'/** @see User::$registrationDate */)
            ->setParameter('dayStart', $startDay->format('Y-m-d'))
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * Поиск клиентов, которым нужно перезвонить в ближайшие минуты.
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return User[]
     */
    public function findClientsForRecall(
        \DateTime $dateFrom,
        \DateTime $dateTo
    ): array {
        $qb = $this->createQueryBuilder('user');
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->in('user.status'/** @see User::$status */, ':statuses'),
                $qb->expr()->gt('user.recallAt'/** @see User::$recallAt */, ':start'),
                $qb->expr()->lt('user.recallAt'/** @see User::$recallAt */, ':end')
            )
        );
        $qb->setParameter('statuses', UserStatusType::SET_RECALL_STATUSES);
        $qb->setParameter('start', $dateFrom);
        $qb->setParameter('end', $dateTo);
        $qb->orderBy('user.recallAt'/** @see User::$recallAt */, Criteria::ASC);

        return $qb->getQuery()->getResult();
    }

    public function findUsersWithBirthday(
        \DateTime $dateBirthday,
        int $daysLateMax,
        int $productType,
        bool $excludeBlocked = true
    ): array {
        $qb = $this->createQueryBuilder('user');
        $expr = $qb->expr();

        $qb
            ->join('user.registrations', 'user_registrations')
            ->join('user.loans', 'user_loans')
            ->leftJoin('user.loans', 'user_loans_confirmed', Join::WITH, 'user_loans_confirmed.status IN (:confirmedStatuses)')
            ->where('date_format(user.birthDate, \'%m%d\') = :birthday')
            ->andWhere('user_registrations.project = :project')
            ->andWhere('user_loans.daysLateMax < :daysLateMax OR user_loans.daysLateMax IS NULL')
            ->andWhere(
                $expr->in(
                    'user_loans.id',
                    $this
                        ->_em->createQueryBuilder()
                        ->select('MAX(loan.id)')
                        ->from(Loan::class, 'loan')
                        ->join('loan.product', 'loan_product')
                        ->where('loan.user = user.id')
                        ->andWhere('loan.status IN (:returnedStatuses)')
                        ->andWhere('loan_product.type = :productType')
                        ->getDQL()
                )
            );

        // Только те кого нет в blacklist у кого не истёк срок блокировки и последнии заявка была отклонена
        if ($excludeBlocked) {
            $qb->leftJoin(
                BlackListEntry::class,
                'blacklist',
                'WITH',
                'blacklist.persId = user.persId'/** @see BlackListEntry::$persId *//** @see User::$persId */
            );

            $qb->andWhere(
                $expr->andX(
                    $expr->isNull('blacklist'),
                    $expr->notIn('user_loans.status'/** @see Loan::$status */, LoanStatusType::STATUS_DENIED),
                    $expr->orX(
                        $expr->isNull('user.denyTillDate'/** @see User::$denyTillDate */),
                        $expr->lt('user.denyTillDate'/** @see User::$denyTillDate */, ':denyTillDate')
                    )
                )
            );

            $qb->setParameter('denyTillDate', Carbon::now());
        }

        $qb
            ->groupBy('user.id')
            ->having('COUNT(user_loans_confirmed.id) = 0');

        $qb->setParameter('project', Product::TYPE_TO_PROJECT[$productType]);
        $qb->setParameter('birthday', $dateBirthday->format('md'));
        $qb->setParameter('productType', $productType);
        $qb->setParameter('daysLateMax', $daysLateMax);
        $qb->setParameter('confirmedStatuses', LoanStatusType::GROUP_CONFIRMED_STATUSES);
        $qb->setParameter('returnedStatuses', LoanStatusType::GROUP_END_STATUSES);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return User[]
     * @throws Exception
     */
    public function findClientsForContactCenterCompanyDidNotCompleteRegistration(
        \DateTime $dateFrom,
        \DateTime $dateTo
    ): array {
        $qb = $this->createQueryBuilder('user');
        $qb->where(
            $qb->expr()->andX(
                $qb->expr()->eq('user.confirmedBySms'/** @see User::$confirmedBySms */, ':confirmedBySms'),
                $qb->expr()->gt('user.registrationDate'/** @see User::$registrationDate */, ':start'),
                $qb->expr()->lt('user.registrationDate'/** @see User::$registrationDate */, ':end')
            )
        );

        $qb->setParameter('confirmedBySms', false);
        $qb->setParameter('start', $dateFrom);
        $qb->setParameter('end', $dateTo);

        PartnerRepository::applyFilterByPartner($qb);

        return $qb->getQuery()->getResult();
    }

    public function findClientsForOfferRepeatLoanAfterDays(array $days): array
    {
        $qb = $this->createQueryBuilder('user');

        PartnerRepository::applyFilterByPartner($qb);

        return $qb
            ->join('user.loans', 'user_loans', Join::WITH, 'user_loans.status IN (:returnedStatuses)')
            ->leftJoin('user.loans', 'active_loans', Join::WITH, 'active_loans.status IN (:activeStatus)')
            ->where('DATE_DIFF(CURRENT_DATE(), user_loans.returnedDate) IN (:days)')
            ->andWhere('user_loans.daysLateMax < 30 OR user_loans.daysLateMax IS NULL')
            ->andWhere(
                $qb->expr()->in(
                    'user_loans.id',
                    $this->_em->createQueryBuilder()
                        ->select('MAX(loan.id)')
                        ->from(Loan::class, 'loan')
                        ->join('loan.product', 'loan_product')
                        ->where('loan.user = user.id AND loan.status IN (:returnedStatuses)')
                        ->andWhere('loan_product.type = :productType')
                        ->getDQL()
                )
            )
            ->groupBy('user.id')
            ->having('COUNT(active_loans.id) = 0')
            ->setParameter('productType', Product::TYPE_LONG)
            ->setParameter('activeStatus', LoanStatusType::GROUP_ACTIVE_STATUSES)
            ->setParameter('returnedStatuses', LoanStatusType::GROUP_END_STATUSES)
            ->setParameter('days', $days)
            ->getQuery()
            ->getResult();
    }

    public function findClientsForOfferDiscountLoanAfterReturnedDays(array $days): array
    {
        $qb = $this->createQueryBuilder('user', 'user.id');

        PartnerRepository::applyFilterByPartner($qb);

        return $qb
            ->join('user.loans', 'user_loans')
            ->join('user_loans.product', 'loan_product')
            ->join('user.loans', 'returned_loans', Join::WITH, 'returned_loans.status IN (:returnedStatuses)')
            ->andWhere(
                $qb->expr()->in(
                    'returned_loans.id',
                    $this->_em->createQueryBuilder()
                        ->select('MAX(loan.id)')
                        ->from(Loan::class, 'loan')
                        ->join('loan.product', 'sub_loan_product')
                        ->where('loan.user = user.id AND loan.status IN (:returnedStatuses)')
                        ->andWhere('sub_loan_product.type = :productType')
                        ->getDQL()
                )
            )
            ->andWhere('DATE_DIFF(CURRENT_DATE(), returned_loans.returnedDate) IN (:days)')
            ->andWhere('returned_loans.daysLateMax < 10 OR returned_loans.daysLateMax IS NULL')
            ->groupBy('user.id')
            // Клиент имеет не менее чем 2 возвращенных кредита
            ->having('SUM(CASE WHEN user_loans.status IN (:returnedStatuses) AND loan_product.type = :productType THEN 1 ELSE 0 END) >= 2')
            // У клиента нет активных кредитов или заявок
            ->andHaving('SUM(CASE WHEN user_loans.status IN (:activeStatus) THEN 1 ELSE 0 END) = 0')
            ->setParameter('returnedStatuses', LoanStatusType::GROUP_END_STATUSES)
            ->setParameter(
                'activeStatus',
                [...LoanStatusType::GROUP_ACTIVE_STATUSES, ...LoanStatusType::GROUP_REQUEST_STATUSES]
            )
            ->setParameter('productType', Product::TYPE_SHORT)
            ->setParameter('days', $days)
            ->getQuery()
            ->getResult();
    }

    public function hasActiveRequest(User $user): bool
    {
        return null !== $this->getActiveRequestLoanId($user);
    }

    private function getLoanWithStatusesForUser(
        User $user,
        array $loanStatuses,
        ?string $project = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('user')
            ->select('loan.id')
            ->join('user.loans', 'loan')
            ->where('user.id = :user')
            ->andWhere('loan.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', $loanStatuses);

        if ($project) {
            $qb->join('loan.product', 'product')
                ->andWhere('product.type = :type')
                ->setParameter('type', array_flip(Product::TYPE_TO_PROJECT)[$project])
            ;
        }

        return $qb;
    }

    public function getActiveRequestLoanId(User $user, ?string $project = null): ?int
    {
        $loans = $this
            ->getLoanWithStatusesForUser($user, LoanStatusType::GROUP_REQUEST_STATUSES, $project)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        $first = array_shift($loans);

        if ($first) {
            return $first['id'];
        }

        return null;
    }

    public function hasActiveLoan(User $user): bool
    {
        $loans = $this
            ->getLoanWithStatusesForUser($user, LoanStatusType::GROUP_ACTIVE_STATUSES)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        $first = array_shift($loans);

        return null !== $first;
    }

    public function findOneByCnp(string $idnp): ?User
    {
        return $this->findOneBy(['persId' => $idnp], ['confirmedBySms' => 'DESC']);
    }

    public function getSimilarByParamsIP(User $user, array $excludedIps): array
    {
        $qb = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.registrationIp = :currentValue');

        if ($excludedIps) {
            $qb->andWhere(
                $qb->expr()->notIn('u.registrationIp', $excludedIps)
            );
        }

        return $qb
            ->andWhere('u.id != :currentUserId')
            ->setParameter('currentUserId', $user->getId())
            ->setParameter('currentValue', (string) $user->getRegistrationIp())
            ->getQuery()
            ->getResult();
    }

    public function getSimilarByParamsAddress(User $user): array
    {
        $registrationAddress = $user->getAddress(ContactAddressType::TYPE_REGISTRATION);

        if (!$registrationAddress) {
            return [];
        }

        return $this
            ->createQueryBuilder('u')
            ->select('u')
            ->innerJoin('u.addresses', 'address')
            ->where('address.region = :region')
            ->andWhere('address.village = :village')
            ->andWhere('address.street = :street')
            ->andWhere('address.house = :house')
            ->andWhere('address.apartment = :apartment')
            ->andWhere('u.id != :currentUserId')
            ->setParameter('currentUserId', $user->getId())
            ->setParameter('region', $registrationAddress->getRegion())
            ->setParameter('village', $registrationAddress->getVillage())
            ->setParameter('street', $registrationAddress->getStreet())
            ->setParameter('house', $registrationAddress->getHouse())
            ->setParameter('apartment', $registrationAddress->getApartment())
            ->getQuery()
            ->getResult();
    }

    public function getSimilarByParamsPassport(User $user): array
    {
        return $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('u.persId2 = :currentValue')
            ->andWhere('u.id != :currentUserId')
            ->setParameter('currentUserId', $user->getId())
            ->setParameter('currentValue', $user->getPersId2())
            ->getQuery()
            ->getResult();
    }

    public function getSimilarByParamsGiveoutAccount(UserAccount $account): array
    {
        return $this
            ->createQueryBuilder('u')
            ->select('u')
            ->innerJoin('u.accounts', 'account')
            ->where('account.account = :currentValue')
            ->andWhere('u.id != :currentUserId')
            ->andWhere('account.giveoutMethod = :currentMethod')
            ->setParameter('currentUserId', $account->getUser()->getId())
            ->setParameter('currentValue', $account->getAccount())
            ->setParameter('currentMethod', $account->getGiveoutMethod())
            ->getQuery()
            ->getResult();
    }

    public function getSimilarByParamsPhone(User $user, string $number, bool $forAccount = false): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->leftJoin('u.contactPhones', 'userContactPhones')
            ->where('userContactPhones.number = :currentValue')
            ->orWhere('u.mainPhoneNumber = :currentValue')
            ->andWhere('u.id != :currentUserId')
            ->setParameter('currentUserId', $user->getId())
            ->setParameter('currentValue', $number);

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function getSimilarByParamsEmail(User $user, string $currentEmail): array
    {
        return $this
            ->createQueryBuilder('u')
            ->select('u')
            ->innerJoin('u.emails', 'email')
            ->where('email.email = :currentValue')
            ->andWhere('u.id != :currentUserId')
            ->setParameter('currentUserId', $user->getId())
            ->setParameter('currentValue', $currentEmail)
            ->getQuery()
            ->getResult();
    }

    public function getOneByPhone(string $phone): User
    {
        $user = $this->findOneBy(['mainPhoneNumber' => $phone]);

        if (null === $user) {
            throw new \DomainException('Not found user by mainPhoneNumber#' . $phone);
        }

        return $user;
    }

    public function getOneOrNullByPhoneEnd(string $phoneEnd): ?User
    {
        return $this
            ->createQueryBuilder('u')
            ->where('u.mainPhoneNumber LIKE :phoneEnd')
            ->setParameter('phoneEnd', '%' . $phoneEnd)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getSimilarByParamsPersId(User $user, string $persId): array
    {
        return $this
            ->createQueryBuilder('u')
            ->where('u.persId = :persId')
            ->andWhere('u.id != :user_id')
            ->setParameters([
                'persId' => $persId,
                'user_id' => $user->getId(),
            ])
            ->getQuery()
            ->getResult();
    }

    public function getSimilarByParamsIban(User $user, string $iban): array
    {
        return $this
            ->createQueryBuilder('u')
            ->leftJoin('u.accounts', 'a')
            ->where('a.account = :iban')
            ->andWhere('u.id != :user_id')
            ->setParameters([
                'iban' => $iban,
                'user_id' => $user->getId(),
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findUnfinishedRegistrationWithPersId(
        ?int $startFromUserNumber = null,
        ?int $limitUsers = null,
        ?string $lastActivityFrom = null,
        ?string $lastActivityTo = null,
        ?string $registrationFrom = null,
        ?string $registrationTo = null,
        ?string $smsTemplateNotExist = null,
    ): array {

        $qb = $this->createQueryBuilder('u');

        $qb
            ->leftJoin('u.loans', 'l')
            ->where('l.id is null')
            ->andWhere('u.persId is not null')
            ->andWhere('u.mainPhoneNumber is not null')
            ->orderBy('u.id', Criteria::ASC);

        if ($startFromUserNumber) {
            $qb->setFirstResult($startFromUserNumber);
        }

        if ($limitUsers) {
            $qb->setMaxResults($limitUsers);
        }

        if ($lastActivityFrom) {
            $qb
                ->andWhere('u.lastActivity >= :lastActivityFrom')
                ->setParameter('lastActivityFrom', $lastActivityFrom);
        }

        if ($lastActivityTo) {
            $qb
                ->andWhere('u.lastActivity <= :lastActivityTo')
                ->setParameter('lastActivityTo', $lastActivityTo);
        }

        if ($registrationFrom) {
            $qb
                ->andWhere('u.registrationDate >= :registrationFrom')
                ->setParameter('registrationFrom', $registrationFrom);
        }

        if ($registrationTo) {
            $qb
                ->andWhere('u.registrationDate <= :registrationTo')
                ->setParameter('registrationTo', $registrationTo);
        }

        if ($smsTemplateNotExist) {
            $subQb = $this->getEntityManager()->createQueryBuilder();
            $subQb
                ->select('COUNT(templateSms)')
                ->from(Sms::class, 'templateSms')
                ->where('templateSms.template = :template')
                ->andWhere('templateSms.user = u.id');

            $qb
                ->andWhere('(' . $subQb->getDQL() . ') = 0')
                ->setParameter(':template', $smsTemplateNotExist);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findUnfinishedRegistrationWithoutDuplicatesWithPersId(
        ?int $startFromUserNumber = null,
        ?int $limitUsers = null,
        ?string $registrationFrom = null,
        ?string $registrationTo = null,
    ): array {
        $subQb = $this->createQueryBuilder('ud');
        $userDuplicates = $subQb
            ->resetDQLParts()
            ->select('ud')
            ->from(UserDuplicate::class, 'ud')
            ->where('ud.duplicateProp = :prop')
            ->setParameter('prop', UserDuplicatePropType::USER_PROP_PERS_ID)
            ->getQuery()
            ->getResult();

        $userDuplicateIds = [];
        /** @var UserDuplicate $userDuplicate */
        foreach ($userDuplicates as $userDuplicate) {
            if (null === $userDuplicate->getDuplicateUser()) {
                continue;
            }
            $userDuplicateIds[] = $userDuplicate->getDuplicateUser()->getId();
        }

        $qb = $this->createQueryBuilder('u');

        $qb
            ->leftJoin('u.loans', 'l')
            ->leftJoin('u.userDuplicates', 'ud')
            ->where('l.id is null')
            ->andWhere('u.persId is not null')
            ->andWhere('u.status != :status')
            ->andWhere('u.mainPhoneNumber is not null')
            ->andWhere($qb->expr()->notIn('u.id', ':userDuplicateIds'))
            ->setParameters([
                'status' => UserStatusType::STATUS_NOT_ELIGIBLE,
                'userDuplicateIds' => $userDuplicateIds,
            ])
            ->orderBy('u.id', Criteria::ASC);

        if ($startFromUserNumber) {
            $qb->setFirstResult($startFromUserNumber);
        }

        if ($limitUsers) {
            $qb->setMaxResults($limitUsers);
        }

        if ($registrationFrom) {
            $qb
                ->andWhere('u.registrationDate >= :registrationFrom')
                ->setParameter('registrationFrom', $registrationFrom);
        }

        if ($registrationTo) {
            $qb
                ->andWhere('u.registrationDate <= :registrationTo')
                ->setParameter('registrationTo', $registrationTo);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findUnfinishedRegistrationsWithUtmSource(
        CarbonInterface $from,
        CarbonInterface $to,
        string $utmSource,
        string $templateKey,
    ): array {
        $qb = $this->createQueryBuilder('u');
        $subQb = $qb->getEntityManager()->createQueryBuilder();

        $subQb
            ->select('count(sms.id)')
            ->from(Sms::class, 'sms')
            ->where('sms.user = u.id')
            ->andWhere($qb->expr()->eq('sms.template', ':template'));

        return $qb
            ->leftJoin('u.loans', 'l')
            ->leftJoin('u.userDuplicates', 'ud')
            ->where($qb->expr()->isNull('l.id'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('ud.id'),
                    $qb->expr()->neq('ud.duplicateProp', ':duplicateProp'),
                    $qb->expr()->isNull('ud.mergeStopFactors'),
                )
            )
            ->andWhere($qb->expr()->eq('u.confirmedBySms', '1'))
            ->andWhere($qb->expr()->between('u.lastActivity', ':from', ':to'))
            ->andWhere($qb->expr()->like('u.targetUrl', ':utmSource'))
            ->andWhere('(' . $subQb->getDQL() . ') = 0')
            ->setParameter("from", $from)
            ->setParameter('to', $to)
            ->setParameter('utmSource', '%utm_source=' . $utmSource . '%')
            ->setParameter('template', $templateKey)
            ->setParameter('duplicateProp', UserDuplicatePropType::USER_PROP_PERS_ID)
            ->getQuery()
            ->getResult();
    }

    public function findUsersWithLegalLoans(User $user = null): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb
            ->leftJoin('user.loans', 'l')
            ->where($qb->expr()->in('l.status', LoanStatusType::GROUP_COURT));
        if ($user) {
            $qb->andWhere('user = :user');
            $qb->setParameter('user', $user);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findUsersByPhoneNumber(string $phone): array
    {
        $phone = '%' . preg_replace('/\D/', '', $phone) . '%';
        $qb = $this->createQueryBuilder('user');
        $qb->leftJoin('user.contactPhones', 'phones')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('user.mainPhoneNumber', ':phone'),
                    $qb->expr()->like('phones.number', ':phone'),
                )
            )
            ->setParameter('phone', $phone);

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function getUserUnconfirmedNumber(): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb
            ->select('user')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('user.confirmedBySms', ':state'),
                    $qb->expr()->lte('user.lastActivity', ':monthsAgo')
                )
            )
            ->setParameter('state', 'false')
            ->setParameter('monthsAgo', Carbon::now()->subMonths(self::THREE_MONTHS_AGO));

        return $qb->getQuery()->getResult();
    }

    public function getUserNoCnp(): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb
            ->select('user')
            ->where('user.lastActivity <= :monthsAgo')
            ->andWhere('user.persId IS NULL')
            ->setParameter('monthsAgo', Carbon::now()->subMonths(self::SIX_MONTHS_AGO));

        return $qb->getQuery()->getResult();
    }

    public function getUserWithSmsStatusValuesUndelivered(): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb
            ->select('user.id', 'user.firstName')
            ->innerJoin(Sms::class, 's', 'WITH', 's.user = user.id')
            ->where(
                $qb->expr()->in('s.status', ':statuses')
            )
            ->groupBy('user.id')
            ->having($qb->expr()->gt($qb->expr()->count('s.id'), ':count'))
            ->setParameter('statuses', SmsStatusType::VALUES_UNDELIVERED)
            ->setParameter('count', 5);

        return $qb->getQuery()->getResult();
    }

    public function getUserNotFinishedRegistration(): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->where(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $this->_em->createQueryBuilder()
                                  ->select('l')
                                  ->from(Loan::class, 'l')
                                  ->where('l.user = u.id')
                                  ->getDQL()
                    )
                )
            )
            ->andWhere(
                $qb->expr()->lte('u.lastActivity', ':timeAgo')
            )
            ->setParameter('timeAgo', Carbon::now()->subMonths(self::TWELVE_MONTHS_AGO));

        return $qb->getQuery()->getResult();
    }
}
