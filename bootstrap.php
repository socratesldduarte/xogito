<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

//
//$user = new \Src\Models\User($dbConnection);
//
//
//// return all records
//echo "TODOS OS USUÁRIOS\n";
//$result = $user->findAll();
//
//echo var_dump($result) . "\n";
//
////ADICIONAR UM NOVO USUÁRIO
//
//echo "INSERIR UM USUÁRIO\n";
//$result = $user->insert([
//    'name' => 'Novo Usuário',
//    'email' => 'socrates231@swge.com.br',
//    'password' => 'maisumteste',
//    'is_administrative' => false,
//]);
//
//echo var_dump($result) . "\n";
//
//// return all records
//echo "TODOS OS USUÁRIOS\n";
//$result = $user->findAll();
//
//echo var_dump($result) . "\n";
