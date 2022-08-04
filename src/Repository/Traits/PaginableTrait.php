<?php 

namespace App\Repository\Traits;

trait PaginableTrait {
	/**
     * @return Query
     */
    public function getQueryBuilder(array $criteria = [], array $where = [], array $orderBy = []) {
        $queryBuilder = $this->createQueryBuilder('t');
        foreach($criteria as $key => $value) {
            $queryBuilder->andWhere("t.$key = :$key");
        }
        foreach($where as $key => $value) {
            $queryBuilder->andWhere("t.$key $value");
        }
        foreach($orderBy as $key => $value) {
            $queryBuilder->orderBy("t.$key", $value);
        }

        $queryBuilder->setParameters($criteria);

        return $queryBuilder;
    }
}