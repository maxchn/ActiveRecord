<?php

spl_autoload_register(function ($className) {
    require_once "$className.php";
});

use db\Products;

echo "\n=== Adding new records ===\n";
$tomato = new Products();
$tomato->name = "A tomato";
$tomato->price = 20;
$tomato->discount = 1;
$tomato->description = "Juicy beautiful tomatoes";
$tomato->save();

$lemon = new Products();
$lemon->name = "Lemon";
$lemon->price = 90;
$lemon->discount = 4;
$lemon->description = "Citrus";
$lemon->save();

$watermelon = new Products();
$watermelon->name = "Watermelon";
$watermelon->price = 70;
$watermelon->discount = 0;
$watermelon->description = "Delicious, juicy fruit";
$watermelon->save();

$garlic = new Products();
$garlic->name = "garlic";
$garlic->price = 40;
$garlic->discount = 4;
$garlic->description = "Delicious, juicy fruit";
$garlic->save();
$garlic->description = "Heals against influenza";
$garlic->name = "Garlic";
$garlic->save();
echo "\n----------------------------------------------------------\n";

echo "\n=== Find by primary key ===\n";
$product = Products::findByPk(4);
var_dump($product);
if(!is_null($product)) {
    $product->price = 20;
    $product->save();
}
echo "\n----------------------------------------------------------\n";

echo "\n=== Find first record ===\n";
$product = Products::find("price=:price", array(":price"=>20));
var_dump($product);
echo "\n----------------------------------------------------------\n";

echo "\n=== Find all records ===\n";
$products = Products::findAll("price=:price", array(":price"=>20));
var_dump($products);
echo "\n----------------------------------------------------------\n";

echo "\n=== Getting the number of records that match the specified criteria ===\n";
$count = Products::count("price=:price", array(":price"=>20));
var_dump($count);
echo "\n----------------------------------------------------------\n";

echo "\n=== Find first record and her remove ===\n";
$product = Products::find("price=:price", array(":price"=>20));
var_dump($product);
if(!is_null($product)) {
    echo ($product->delete() === FALSE ? "Failure" : "Success") ;
}
echo "\n----------------------------------------------------------\n";