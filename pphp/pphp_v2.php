<?php
/*
* Author: Wadson Pontes
* Date: 20/07/2019
* Version: 2.0
*/

// include "pphp.php";
date_default_timezone_set("America/Fortaleza");
// error_reporting(0);
error_reporting(E_ALL);

$SERVIDOR = "";
$USUARIO = "";
$SENHA = "";
$BANCO = "";
$TABELA = "";

$CONEXAO = "";
$RESPOSTA = "";
$IP = $_SERVER["REMOTE_ADDR"];
$INFO = $_SERVER["HTTP_USER_AGENT"];

function servidor() {
	global $SERVIDOR;

	if (tam(func_get_args()) === 0) return $SERVIDOR;
	$SERVIDOR = func_get_args()[0];
}

function usuario() {
	global $USUARIO;

	if (tam(func_get_args()) === 0) return $USUARIO;
	$USUARIO = func_get_args()[0];
}

function senha() {
	global $SENHA;

	if (tam(func_get_args()) === 0) return $SENHA;
	$SENHA = func_get_args()[0];
}

function banco() {
	global $BANCO;

	if (tam(func_get_args()) === 0) return $BANCO;
	$BANCO = func_get_args()[0];
}

function tabela() {
	global $TABELA;

	if (tam(func_get_args()) === 0) return $TABELA;
	$TABELA = func_get_args()[0];
}

function conexao() {
	global $CONEXAO;

	if (tam(func_get_args()) === 0) return $CONEXAO;
	$CONEXAO = func_get_args()[0];
}

function resposta() {
	global $RESPOSTA;

	if (tam(func_get_args()) === 0) return $RESPOSTA;
	$RESPOSTA = func_get_args()[0];
}

function ip() {
	global $IP;

	return $IP;
}

function info() {
	global $INFO;

	return $INFO;
}

function id() {
	try {
		return conexao()->lastInsertId();
	}
	catch (PDOException $e) {
		resposta()["erro"] = "Falha no ID: " . $e->getMessage();
		return 0;
	}
}

function conectar() {
	try {
		conexao(new PDO("mysql:host=".servidor().";dbname=".banco().";charset=utf8", usuario(), senha(), array(PDO::ATTR_PERSISTENT => true)));
		conexao()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		conexao()->query("SET time_zone = '-03:00'");
	}
	catch (PDOException $e) {
		resposta()["erro"] = "Falha na conexão: " . $e->getMessage();
	}
}

function desconectar() {
	conexao(null);
}

function qtd(...$condicoes) {
	return quantidade(...$condicoes);
}

function quantidade(...$condicoes) {
	try {
		$sql = gerarSQL("SELECT COUNT(*) FROM ".tabela()." WHERE", ...$condicoes);
		return $sql;
	}
	catch (PDOException $e) {
		resposta()["erro"] = "Falha na quantidade: " . $e->getMessage();
		return 0;
	}
}

function gerarSQL($sql, ...$args) {
	if (tam($args) === 0) return "$sql = 1";
	else if (tam($args) === 1) return $sql.textoSQL($args[0]);
}

function textoSQL($t) {
	$campo = []; $comparador = []; $valor = []; $logico = []; $sql = ""; $r;
	
	while ($r = contemComparador($t)) {
		add($comparador, true, $r["v"]);
		$t = trocar($t, $r["v"], "", -1);
		add($valor, true, subtexto($t, $r["p"]));
		$t = tirarEspacos(subtexto($t, 0, $r["p"] - 1));

		if (contem($t, " ")) {
			add($campo, true, subtexto($t, contem($t, " ", true, true) + 1));
			$t = tirarEspacos(subtexto($t, 0, contem($t, " ", true, true) - 1));
			add($logico, true, subtexto($t, contem($t, " ", true, true) + 1));
			$t = tirarEspacos(subtexto($t, 0, contem($t, " ", true, true) - 1));
		}
		else {
			add($campo, true, $t);
			$t = "";
		}
	}
	for ($i = 0; $i < tam($valor); $i++) {
		$comparador[$i] = comparador($comparador[$i]);
		$sql .= " ".$campo[$i]." ".$comparador[$i]." ".$valor[$i];
		if ($i < tam($valor) - 1) {
			$logico[$i] = logico($logico[$i]);
			$sql .= " ".$logico[$i];
		}
	}
	return $sql;
}

function tam($v) {
	return tamanho($v);
}

function tamanho($v) {
	if (tipo($v) === "texto") return mb_strlen($v);
	return count($v);
}

function add(&$vetor, $inicio, ...$valores) {
	if ($inicio === true) array_unshift($vetor, ...$valores);
	else array_push($vetor, ...$valores);
}

function palavra($palavra) {
	return maiuscula($palavra) !== minuscula($palavra);
}

function trocar($texto, $velho, $novo, $numero = 1, $sensivel = true) {
    $ultimo = $numero > 0 ? false : true;
    $posicao;

	if ($numero === 0) $numero = tam($texto);

	for ($i = 0; $i < abs($numero); $i++) {
		if (($posicao = contem($texto, $velho, $sensivel, $ultimo)) === false) break;
		$texto = subtexto($texto, 0, $posicao - 1, false).$novo.subtexto($texto, $posicao + tam($velho));
	}
	return $texto;
}

function subtexto($t, $i, $f = NULL, $negativos = true) {
    if ($f === NULL) $f = tam($t) - 1;
    if ($negativos === false AND $i < 0) $i = 0;
    if ($negativos === false AND $f < 0) return "";
	return mb_substr($t, $i, ($f >= 0 ? $f : (tam($t) + $f)) - ($i >= 0 ? $i : tam($t) + $i) + 1);
}

function contem($texto, $palavra, $sensivel = true, $ultimo = false) {
	if ($sensivel === true AND $ultimo === false) return mb_strpos($texto, $palavra);
	if ($sensivel === true AND $ultimo === true) return mb_strrpos($texto, $palavra);
	if ($sensivel === false AND $ultimo === true) return mb_strripos($texto, $palavra);
	return mb_stripos($texto, $palavra);
}

function quebrar($separador, $texto) {
	return explode($separador, $texto);
}

function juntar($separador, $texto) {
	return implode($separador, $texto);
}

function tirarEspacos($texto) {
	return trim($texto);
}

function maiuscula($palavra) {
	return mb_strtoupper($palavra, 'UTF-8');
}

function minuscula($palavra) {
	return mb_strtolower($palavra, 'UTF-8');
}

function tipo($v) {
	switch (gettype($v)) {
		case "boolean": return "booleano";
		case "integer": return "inteiro";
		case "double": return "real";
		case "string": return "texto";
		case "array": return "vetor";
		case "object": return "objeto";
		case "resource": return "recurso";
		case "NULL": return "nulo";
		case "unknown type": return "desconhecido";
	}
}

function comparador($op) {
	if ($op === "=" OR $op === "==") return "=";
	else if ($op === ">") return ">";
	else if ($op === "<") return "<";
	else if ($op === ">=" OR $op === "=>") return ">=";
	else if ($op === "<=" OR $op === "=<") return "<=";
	else if ($op === "!=" OR $op === "=!" OR $op === "<>") return "!=";
	return false;
}

function contemComparador($t) {
	$r = [];
	$p1; $p2;

	$p1 = max(contem($t, "=", true, true), contem($t, ">", true, true), contem($t, "<", true, true));
	$p2 = max(contem($t, "==", true, true), contem($t, ">=", true, true), contem($t, "=>", true, true), contem($t, "<=", true, true), contem($t, "=<", true, true), contem($t, "!=", true, true), contem($t, "=!", true, true), contem($t, "<>", true, true));
	if ($p2 + 2 > $p1) $r["p"] = $p2;
	else $r["p"] = $p1;

	if ($r["p"] === false) return false;
	if (contem($t, "==", true, true) === $r["p"]) $r["v"] = "==";
	else if (contem($t, ">=", true, true) === $r["p"]) $r["v"] = ">=";
	else if (contem($t, "=>", true, true) === $r["p"]) $r["v"] = "=>";
	else if (contem($t, "<=", true, true) === $r["p"]) $r["v"] = "<=";
	else if (contem($t, "=<", true, true) === $r["p"]) $r["v"] = "=<";
	else if (contem($t, "!=", true, true) === $r["p"]) $r["v"] = "!=";
	else if (contem($t, "=!", true, true) === $r["p"]) $r["v"] = "=!";
	else if (contem($t, "<>", true, true) === $r["p"]) $r["v"] = "<>";
	else if (contem($t, "=", true, true) === $r["p"]) $r["v"] = "=";
	else if (contem($t, ">", true, true) === $r["p"]) $r["v"] = ">";
	else if (contem($t, "<", true, true) === $r["p"]) $r["v"] = "<";
	return $r;
}

function logico($op) {
	$op = minuscula($op);

	if ($op === "and" OR $op === "e" OR $op === "&&" OR $op === "&") return "AND";
	else if ($op === "or" OR $op === "ou" OR $op === "||" OR $op === "|") return "OR";
	else return false;
}

function iniciarAPI() {
	// conectar();
	echo qtd("nome != Thiago E (descricao == Olá Mundo! OU id <> 4)");
}

iniciarAPI();