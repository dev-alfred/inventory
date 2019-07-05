<?php
	foreach(glob("src/*.php") as $files):
		include $files;
	endforeach;
	
	class ProccessOrders implements OrderProcessorInterface, ProductsSoldInterface, InventoryInterface, ProductsPurchasedInterface{
		protected $ordersObj;
		protected $orderInFile;
		protected $SoldPerDay = [];
		
		// counter
		protected $inventory = [ 1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20];
		protected $sold = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
		protected $totalpending = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
		protected $totalreceived = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
		
		// for handling request for stocks
		protected $pending = [ 
				1 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				2 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				3 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				4 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				5 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ] 
			];
		protected $received = [ 
				1 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				2 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				3 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				4 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ], 
				5 => [ 0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0 ] 
			];
			
			
		public function processFromJson($path = "") :void{
			if(!empty($path)):
				$this->ordersObj = json_decode(file_get_contents($path),true);
			else:
				$this->ordersObj = '';
			endif;
		}
		
		public function getAllOrder() :array{
			return is_array($this->ordersObj) ? $this->ordersObj : [];
		}
		
		public function processJson(int $dayid) :array{
			if(!empty($this->ordersObj[$dayid])):
				$this->SoldPerDay = $this->ordersObj[$dayid];
			endif;
			return $this->SoldPerDay;
		}
		
		public function proccessOrderPerDay(int $productid, int $dayid) :bool{
			$totalSoldPerDay = 0;
			$purchaseorder = 20;
			foreach($this->SoldPerDay as $orders):
				foreach($orders as $id => $order):
					if($productid == $id):
						$totalSoldPerDay += $order;
					endif;
				endforeach;
			endforeach;
			
			// check for receivable purchaseorder
			if($this->received[$productid][$dayid] > 0 ):
				$this->inventory[$productid] += $this->received[$productid][$dayid];
				$this->totalreceived[$productid] += $this->received[$productid][$dayid];
			endif;
			
			// check for pending purchaseorder
			if($this->pending[$productid][$dayid] > 0 ):
				$this->totalpending[$productid] += $this->pending[$productid][$dayid];
			endif;
			
			// update the stocks after validating the number of stocks
			if($this->inventory[$productid] > 0 && ($this->inventory[$productid] - $totalSoldPerDay) > 0):
				$this->sold[$productid] += $totalSoldPerDay;
				$this->inventory[$productid] -= $totalSoldPerDay;
				return true;
			else:
				return false;
			endif;
		}
		
		// hanldes the purchase order
		public function requestForStocks($productid, $dayid){
			$this->pending[$productid][$dayid + 1] = 20;
			$this->received[$productid][$dayid + 2] = 20;
		}
		
		public function getSoldTotal($productid) :int{
			return $this->sold[$productid];
		}
		
		public function getStockLevel($productid) :int{
			return $this->inventory[$productid];
		}
		
		public function getPurchasedReceivedTotal($productid) :int{
			return $this->totalreceived[$productid];
		}
		
		public function getPurchasedPendingTotal($productid) :int{
			return $this->totalpending[$productid];
		}
		
		public function print_received(){
			return $this->received;
		}
		
		public function print_pending(){
			return $this->pending;
		}
		
		//** file validation
		
		public function processFile($file) :void{
			$this->orderInFile = $file;
		}
		
		public function validateOrder($file = []) :bool{
			if(count($file) == 7){
				return true;
			}
			return false;
		}
		
		public function validateType($file) :bool{
			if($file == 'application/json'):
				return true;
			endif;
			
			return false;
		}
		
		public function getTmpPath() :string{
			return $this->orderInFile['OrderInFile']['tmp_name'];
		}
		
		public function getFileType() :string{
			return $this->orderInFile['OrderInFile']['type'];
		}
	
	}
	
	
	$file = '';
	$type = '';
	$orders = [];
	$data = [];
	$product_names = [];
	$product_name = '';
	$stop_proccess = false;
	$firstload = true;
	$isSuccess = true;
	$order = new ProccessOrders;
	
	if(isset($_POST["submit"])) {
		$order->processFile($_FILES);
		
		$file = $order->getTmpPath();
		$type = $order->getFileType();
		$firstload = false;
		if(!$order->validateType($type)):
			$stop_proccess = true;
			$data['msg'] = 'Invalid file type (must be json file).';
		endif;
	}
	$order->processFromJson($file);
	// unlink($file);
	
	// Product Names
	$prod = new ReflectionClass('Products');
	$product_names = $prod->getConstants();
	$days = 7;
	
	$orders = $order->getAllOrder();
	if(!$stop_proccess):
		if($order->validateOrder($orders) || $firstload):
		
			for($day = 0; $days >= $day + 1; $day++):
				$order->processJson($day);
				for($productid = 1; count($product_names) >= $productid; $productid++):
					$isSuccess = $order->proccessOrderPerDay($productid, $day);
					if(!$isSuccess):
						$stop_proccess = true;
						$data['msg'] = 'Cannot be fulfilled because there is no stock for day '.($day + 1);
					endif;
					
					$totalSoldPerDay = $order->getSoldTotal($productid);
					$stocks = $order->getStockLevel($productid);
					if($stocks < 10):
						$order->requestForStocks($productid, $day);
					endif;
					
					$pending = $order->getPurchasedPendingTotal($productid);
					$received = $order->getPurchasedReceivedTotal($productid);
					
					if($day == 6):
						
						foreach($product_names as $name => $id):
							if($id == $productid):
								$product_name = $name;
							endif;
						endforeach;
						
						$data[] = [
							'id' => $productid,
							'name' => ucfirst(str_replace("_", " ", $product_name)),
							'stocks' => $stocks,
							'solds' => $totalSoldPerDay,
							'pending' => $pending,
							'received' => $received
						];
						// echo " product id: ".ucfirst(str_replace("_", " ", $product_name))." total sold: ".$totalSoldPerDay." Inventory: ".$stocks." Total Pending: ".$pending." Total Received: ".$received."<br />";
					endif;
				endfor;
			endfor;
		else:
			$stop_proccess = true;
			$data['msg'] = "Invalid Order format";
		endif;
	endif;
	
?>
<html>
	<head>
		<title>Inventory Coding Challenge</title>
		<style>
			.header{
				color: white;
				background: red;
			}
			.header td {
					width: 20%;
				}
			
			tbody tr:nth-child(odd) {background-color: #f2f2f2;}
			
			.report{
				width: 70%;
				margin: auto;    
				box-shadow: -3px 5px 0 0px #f1d0d0;
			}
			
			.FileUpload{
				width: 30%;
				margin: 10% auto auto auto;
				background-color: #ff0000b5;
				padding: 20px 0px 20px 50px;
				color: white;
				font-weight: bold;
				box-shadow: -5px 5px #acacac;
			}

			
			.report table{
				width : 100%;
			}
			
			.report-title{
				text-align: center;
			}
			
			.no-stocks{
				text-align: center;
				padding: 10px;
				background: #ff1717;
				color: white;
			}
			
		</style>
	</head>
	<body>
			<form action="index.php" method="post" enctype="multipart/form-data" class="FileUpload">
				<div>Select Orders to process:</div>
				<input type="file" name="OrderInFile">
				<input type="submit" value="Process Order" name="submit">
			</form>
			<br />
			<br />
		<?php if(!$stop_proccess): ?>
			<h3 class="report-title">Summary Reports for 7 days transactions</h3>
			<div class="report">
			<table>
				<thead>
					<tr class="header">
						<td>Product Name</td>
						<td>Total Solds</td>
						<td>Total Purchase Pending</td>
						<td>Total Purchase Received</td>
						<td>Available Stocks</td>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach($data as $value):
							echo "<tr>";
								echo "<td>".$value['name']."</td>";
								echo "<td>".$value['solds']."</td>";
								echo "<td>".$value['pending']."</td>";
								echo "<td>".$value['received']."</td>";
								echo "<td>".$value['stocks']."</td>";
							echo "</tr>";
						endforeach;
					?>
				</tbody>
			</table>
			</div>
		<?php else: ?>
			<h3 class="report-title">Summary Reports</h3>
			<div class="report">
				<div class="no-stocks"><?php echo $data['msg']; ?></div>
			</div>
		<?php endif; ?>
	</body>
</html>