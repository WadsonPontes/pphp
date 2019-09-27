<?php
// include "pphp.php";
date_default_timezone_set("America/Fortaleza");
// error_reporting(0);
error_reporting(E_ALL);

$SERVIDOR;
$USUARIO;
$SENHA;
$BANCO;
$TABELA;

$CONEXAO;
$RESPOSTA;
$IP = $_SERVER["REMOTE_ADDR"];;
$INFO = $_SERVER["HTTP_USER_AGENT"];

function servidor($valor = null) {
	global $SERVIDOR;

	if ($valor == null) return $SERVIDOR;
	$SERVIDOR = $valor;
}

function usuario($valor = null) {
	global $USUARIO;

	if ($valor == null) return $USUARIO;
	$USUARIO = $valor;
}

function senha($valor = null) {
	global $SENHA;

	if ($valor == null) return $SENHA;
	$SENHA = $valor;
}

function banco($valor = null) {
	global $BANCO;

	if ($valor == null) return $BANCO;
	$BANCO = $valor;
}

function tabela($valor = null) {
	global $TABELA;

	if ($valor == null) return $TABELA;
	$TABELA = $valor;
}

function conexao($valor = -1) {
	global $CONEXAO;

	if ($valor === -1) return $CONEXAO;
	$CONEXAO = $valor;
}

function resposta($valor = null) {
	global $RESPOSTA;

	if ($valor == null) return $RESPOSTA;
	$RESPOSTA = $valor;
}

function ip() {
	global $IP;

	return $IP;
}

function ip() {
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
	global $CONEXAO, $SERVIDOR, $USUARIO, $SENHA, $BANCO, $RESPOSTA;

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

function qtd() {
	return quantidade(...func_get_args());
}

function quantidade() {
	try {
		$sql = gerarSQL("SELECT COUNT(*) FROM ".tabela()." WHERE", ...func_get_args());
		return $sql;
	}
	catch (PDOException $e) {
		resposta()["erro"] = "Falha na quantidade: " . $e->getMessage();
		return 0;
	}
}

function gerarSQL($sql, ...$args) {
	if (tam($args) == 0) return "$sql = 1";
	else if (tam($args) == 1) return $sql.textoSQL($args[0]);
}

function textoSQL($t) {
	$campo = []; $comparador = []; $valor = []; $logico = []; $sql = ""; $r;
	
	while ($r = contemComparador($t)) {
		$comparador = add(true, $comparador, $r["valor"]);
		$t = trocar($t, $r["valor"], "");
		$valor = add(true, $valor, subtexto($t, $r["posicao"]));
		$t = removerEspacos(subtexto($t, 0, $r["posicao"] - 1));

		if (contem($t, " ")) {
			$campo = add(true, subtexto($t, contem($t, " ", true, true) + 1));
			$t = removerEspacos(subtexto($t, 0, contem($t, " ", true, true) - 1));
			$logico = add(true, subtexto($t, contem($t, " ", true, true) + 1));
			$t = removerEspacos(subtexto($t, 0, contem($t, " ", true, true) - 1));
		}
		else {
			$campo = add(true, $t);
			$t = "";
		}
	}
	for ($i = 0; $i < tam($valor); $i++) {
		$sql .= " ".$campo[$i]." ".$comparador[$i]." ".$valor[$i];
		if ($i < tam($valor) - 1) $sql .= " ".$logico[$i];
	}
	return $sql;
}

function tam($v) {
	tamanho($v);
}

function tamanho($v) {
	if (tipo($v) === "texto") return strlen($v);
	return count($v);
}

function add($inicio, $vetor, ...$valores) {
	if (tipo($inicio) != "booleano") {
		add(true, $valores, $vetor);
		$vetor = $inicio;
		$inicio = false;
	}
	if ($inicio) return array_unshift($vetor, ...$valores);
	return array_push($vetor, ...$valores);
}

function palavra($palavra) {
	return maiuscula($palavra) != minuscula($palavra);
}

function trocar($texto, $velho, $novo) {
	return str_replace($velho, $novo, $texto);
}

function subtexto($t, $i, $f = 0) {
	return mb_substr($t, $i, ($f > 0 ? $f : ($f == 0 ? (tam($t) - 1) : (tam($t) + $f))) - ($i >= 0 ? $i : tam($t) + $i));
}

function contem($texto, $palavra, $sensivel = true, $ultimo = false) {
	if ($sensivel == true AND $ultimo == false) return mb_strpos($texto, $palavra);
	if ($sensivel == true AND $ultimo == true) return mb_strrpos($texto, $palavra);
	if ($sensivel == false AND $ultimo == true) return mb_strripos($texto, $palavra);
	return mb_stripos($texto, $palavra);
}

function quebrar($separador, $texto) {
	return explode($separador, $texto);
}

function juntar($separador, $texto) {
	return implode($separador, $texto);
}

function removerEspacos($texto) {
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
	$t = subtexto($t, max(contem($t, "=", true, true), contem($t, "==", true, true), contem($t, ">", true, true), contem($t, "<", true, true), contem($t, ">=", true, true), contem($t, "=>", true, true), contem($t, "<=", true, true), contem($t, "=<", true, true)));

	if ($posicao = contem($t, "==", true, true)) return array("posicao" => $posicao, "valor" => "==");
	else if ($posicao = contem($t, ">=", true, true)) return array("posicao" => $posicao, "valor" => ">=");
	else if ($posicao = contem($t, "=>", true, true)) return array("posicao" => $posicao, "valor" => "=>");
	else if ($posicao = contem($t, "<=", true, true)) return array("posicao" => $posicao, "valor" => "<=");
	else if ($posicao = contem($t, "=<", true, true)) return array("posicao" => $posicao, "valor" => "=<");
	else if ($posicao = contem($t, "=", true, true)) return array("posicao" => $posicao, "valor" => "=");
	else if ($posicao = contem($t, ">", true, true)) return array("posicao" => $posicao, "valor" => ">");
	else if ($posicao = contem($t, "<", true, true)) return array("posicao" => $posicao, "valor" => "<");
	return 0;
}

function logico($op) {
	$op = minuscula($op);

	if ($op === "and" OR $op === "e" OR $op === "&&" OR $op === "&") return "AND";
	else if ($op === "or" OR $op === "ou" OR $op === "||" OR $op === "|") return "OR";
	else return false;
}

function iniciarAPI() {
	// conectar();
	echo qtd("nome = Thiago E (descricao = Olá Mundo! OU id = 4)");
}

iniciarAPI();