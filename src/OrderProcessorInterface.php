<?php

interface OrderProcessorInterface
{
    /**
     * This function receives the path of the json for all the orders of the week,
     * processes all orders for the week,
     * while keeping track of stock levels, units sold and purchased
     * See `orders-sample.json` for example
     *
     * @param string $filePath
     */
    public function processFromJson(string $filePath): void;
	
	
	/** Get All data **/
	public function getAllOrder() :array;
	
	/** proccess the file into array per day **/
	public function processJson(int $dayid) :array;
	
	//** validate data
	public function validateOrder($allOrders = []) :bool;
	
	//** file validation
	public function processFile(string $file) :void;
	public function validateType(string $filetype) :bool;
	public function getTmpPath() :string;
	public function getFileType() :string;
}