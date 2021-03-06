<?php

namespace Jeka\ShopBundle\Document;

class Counter{

    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $next
     */
    protected $next;


    /**
     * Get id
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set next
     *
     * @param int $next
     */
    public function setNext($next)
    {
        $this->next = $next;
    }

    /**
     * Get next
     *
     * @return int $next
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Set id
     *
     * @param custom_id $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
