<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Uri;
use App\Repository\UriRepository;
use App\Struct\PutUriRequest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class UriRepositoryTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->initDatabase($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testRepositoryCanBeInstantiated()
    {
        $repository = $this->entityManager->getRepository(Uri::class);
        $this->assertInstanceOf(UriRepository::class, $repository);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testNewEntityCanBeSavedThroughRepository()
    {
        /** @var UriRepository $repository */
        $repository = $this->entityManager->getRepository(Uri::class);

        $putRequest = new PutUriRequest('www.foo.com');

        $entity = new Uri();
        $entity->setOriginalUrl($putRequest->getUrl());
        $entity->setUrlHash($putRequest->getUrlHash());
        $entity->setShortCode($putRequest->getShortCode());

        $repository->saveUri($entity);

        $entity = $repository->findUriByShortCode($putRequest->getShortCode());
        $this->assertSame(
            $putRequest->getShortCode(),
            $entity->getShortCode()
        );
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUriCanBeFoundByShortCode()
    {
        $this->createDemoRecord();

        /** @var UriRepository $repository */
        $repository = $this->entityManager->getRepository(Uri::class);

        $shortCode = substr(sha1('www.bar.com'), 0, 8);

        $entity = $repository->findUriByShortCode($shortCode);
        $this->assertSame(
            $shortCode,
            $entity->getShortCode()
        );
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUriCanBeFoundByUrlHash()
    {
        $this->createDemoRecord();

        /** @var UriRepository $repository */
        $repository = $this->entityManager->getRepository(Uri::class);

        $hash = sha1('www.bar.com');

        $entity = $repository->findUriByShortOriginalHash($hash);
        $this->assertSame(
            'www.bar.com',
            $entity->getOriginalUrl()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }

    /**
     * @param KernelInterface $kernel
     * @throws \Exception
     */
    private function initDatabase(KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        // drop db
        $input = new ArrayInput([
            'command' => 'doctrine:database:drop',
            '--force' => true

        ]);
        $application->run($input, new NullOutput());

        // create db
        $input = new ArrayInput([
            'command' => 'doctrine:database:create'

        ]);
        $application->run($input, new NullOutput());

        // run migrations
        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true

        ]);
        $application->run($input, new NullOutput());

    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createDemoRecord()
    {
        /** @var UriRepository $repository */
        $repository = $this->entityManager->getRepository(Uri::class);

        $putRequest = new PutUriRequest('www.bar.com');

        $entity = new Uri();
        $entity->setOriginalUrl($putRequest->getUrl());
        $entity->setUrlHash($putRequest->getUrlHash());
        $entity->setShortCode($putRequest->getShortCode());

        $repository->saveUri($entity);
    }
}
