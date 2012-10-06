<?php
namespace Jeka\ShopBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;

class CounterManager
{

    private $class;
    private $documentManager;
    private $repository;

    public function __construct($class, DocumentManager $documentManager)
    {
        $this->class           = $class;
        $this->documentManager = $documentManager;
        $this->repository      = $documentManager->getRepository($this->class);
    }


    /**
     * @param $id string (eq classname, for example "product")
     * @return int
     */
    public function createNextValueFor($id)
    {
        $counter = $this->repository->createQueryBuilder()
            ->findAndUpdate()
            ->field('id')->equals($id)
            ->field('next')->inc(1)
            ->getQuery()
            ->execute();

        if (!$counter) {
            $counter = new Counter();
            $counter->setId($id);
            $counter->setNext(1);
            $this->update($counter, true);
        } else {
            $this->documentManager->refresh($counter);
        }

        return $counter->getNext();
    }

    public function update(Counter $counter, $andFlush = true)
    {
        $this->documentManager->persist($counter);
        if ($andFlush) {
            $this->documentManager->flush();
        }
    }
}
