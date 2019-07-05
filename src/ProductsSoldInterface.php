<?php

interface ProductsSoldInterface
{
    /**
     * @param int $productId
     * @return int
     */
    public function getSoldTotal(int $productId): int;
	
	//** count the order per day **//
    public function proccessOrderPerDay(int $productId, int $dayid): bool;
}
