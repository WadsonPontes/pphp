<?php
date_default_timezone_set("America/Fortaleza");
// error_reporting(0);
error_reporting(E_ALL);

class Conexao {
	private $SERVIDOR;
	private $USUARIO;
	private $SENHA;
	private $BANCO;
	private $CONEXAO;
	public $IP;
	public $INFO;

	function Conexao(string $servidor, string $usuario, string $senha, string $banco) {
		$this->SERVIDOR = $servidor;
		$this->USUARIO = $usuario;
		$this->SENHA = $senha;
		$this->BANCO = $banco;
		$this->IP = $_SERVER["REMOTE_ADDR"];
		$this->INFO = $_SERVER["HTTP_USER_AGENT"];
		$this->conectar();
	}

	private function conectar() {
		try {
		    $this->CONEXAO = new PDO( sprintf( "mysql:host=%s;dbname=%s;charset=utf8", $this->SERVIDOR, $this->BANCO ), $this->USUARIO, $this->SENHA, array(PDO::ATTR_PERSISTENT => true) );
		    $this->CONEXAO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Setando o erro.
		    $atualizacao = $this->CONEXAO->query("SET time_zone = '-03:00'");
		    // echo "Conectado com sucesso!<br>";
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	public function desconectar() {
		$this->CONEXAO = null;
		// echo "Desconectado com sucesso!<br>";
	}

	public function id() {
		return $this->CONEXAO->lastInsertId();
	}

	public function quantidade() {
		// tabela | condição (de forma formal, incluindo aspas simples em strings)
		// tabela | campo | operação | valor.
		// tabela | campo | operação | valor | e/ou | campo | operação | valor.
		$vetor = func_get_args(); $tabela = $vetor[0]; $sql = "SELECT COUNT(*) FROM $tabela WHERE"; $condicao; $valores = []; $qtdCondicoes = $this->contarCondicoes($vetor);
		try {
			if (count($vetor) == 1) {
				$sql = $sql . " true";
			}
			else if (count($vetor) == 2) {
				$separado = $this->separador($vetor[1], $valores);
				$condicao = $separado[0];
				$valores = $separado[1];
				$sql = $sql . " " . $this->condicao($condicao);
			}
			else {
				for ($i = 0; $i < $qtdCondicoes; $i++) {
					$valores[$i] = $vetor[(4 * ($i + 1)) - 1];
					if ( count($vetor) == (4 * ($i + 1)) ) $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i);
					else $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 * ($i + 1)] );
				}
			}
			$busca = $this->CONEXAO->prepare($sql);
			for ($i = 0; $i < count($valores); $i++) {
				$busca->bindParam( ':v' . $i, $valores[$i] );
			}
			$busca->execute();
			// echo "Buscado com sucesso!<br>";
			$retorno = $busca->fetch(PDO::FETCH_ASSOC);
			return $retorno["COUNT(*)"];
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	public function buscar() {
		// tabela | condição (de forma formal, incluindo aspas simples em strings)
		// tabela | condição | "ordem" | campo | ordem.
		// tabela | condição | "limite" | valor.
		// tabela | condição | "ordem" | campo | ordem | "limite" | valor.

		// tabela | campo | operação | valor.
		// tabela | campo | operação | valor | "ordem" | campo | ordem.
		// tabela | campo | operação | valor | "limite" | valor.
		// tabela | campo | operação | valor | "ordem" | campo | ordem | "limite" | valor.

		// tabela | campo | operação | valor | e/ou | campo | operação | valor.
		// tabela | campo | operação | valor | e/ou | campo | operação | valor | "ordem" | campo | ordem.
		// tabela | campo | operação | valor | e/ou | campo | operação | valor | "limite" | valor.
		// tabela | campo | operação | valor | e/ou | campo | operação | valor | "ordem" | campo | ordem | "limite" | valor
		$vetor = func_get_args(); $tabela = $vetor[0]; $sql = "SELECT * FROM $tabela WHERE"; $condicao; $valores = []; $qtdCondicoes = $this->contarCondicoes($vetor);
		try {
			if (count($vetor) == 2 OR mb_strtolower($vetor[2]) === "ordem" OR mb_strtolower($vetor[2]) === "limite") {
				$separado = $this->separador($vetor[1], $valores);
				$condicao = $separado[0];
				$valores = $separado[1];
				if (count($vetor) == 2) $sql = $sql . " " . $this->condicao($condicao);
				else if (mb_strtolower($vetor[2]) === "ordem" AND count($vetor) == 5) $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3], $vetor[4] );
				else if (mb_strtolower($vetor[2]) === "limite") $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3] );
				else $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3], $vetor[4], $vetor[5], $vetor[6] );
			}
			else {
				for ($i = 0; $i < $qtdCondicoes; $i++) {
					$valores[$i] = $vetor[(4 * ($i + 1)) - 1];
					if ( count($vetor) == (4 * ($i + 1)) ) $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i);
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "ordem" AND count($vetor) == (7 + ($i * 4)) ) $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)], $vetor[6 + ($i * 4)] );
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "limite") $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)] );
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "ordem") $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)], $vetor[6 + ($i * 4)], $vetor[7 + ($i * 4)], $vetor[8 + ($i * 4)] );
					else $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 * ($i + 1)] );
				}
			}
			$busca = $this->CONEXAO->prepare($sql);
			for ($i = 0; $i < count($valores); $i++) {
				$busca->bindParam( ':v' . $i, $valores[$i] );
			}
			$busca->execute();
			// echo "Buscado com sucesso!<br>";
			return $busca->fetchAll();
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	public function buscarTodos(String $tabela) { // tabela
		$sql = "SELECT * FROM $tabela WHERE 1";

		try {
			$busca_total = $this->CONEXAO->prepare($sql);
			$busca_total->execute();
			// echo "Buscado com sucesso!<br>";
			return $busca_total->fetchAll();
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	public function atualizar() { // tabela | campo | valor | ... | "quando" | campo | operação | valor |...
		$vetor = func_get_args(); $tabela = $vetor[0]; $sql = "UPDATE $tabela SET"; $condicao; $valores = []; $qtdCondicoes = $this->contarCondicoes($vetor);
		try {
			$j = 1;
			while (mb_strtolower($vetor[$j + 2]) != "quando") {
				$sql = $sql . " " . $vetor[$j] . " = :v" . count($valores) . ",";
				$valores[count($valores)] = $vetor[$j + 1];
				$j += 2;
			}
			$sql = $sql . " " . $vetor[$j] . " = :v" . count($valores) . " WHERE";
			$valores[count($valores)] = $vetor[$j + 1];
			$vetor = $this->reduzirVetor($vetor);

			if (count($vetor) == 2 OR mb_strtolower($vetor[2]) === "ordem" OR mb_strtolower($vetor[2]) === "limite") {
				$separado = $this->separador($vetor[1], $valores);
				$condicao = $separado[0];
				$valores = $separado[1];
				if (count($vetor) == 2) $sql = $sql . " " . $this->condicao($condicao);
				else if (mb_strtolower($vetor[2]) === "ordem" AND count($vetor) == 5) $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3], $vetor[4] );
				else if (mb_strtolower($vetor[2]) === "limite") $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3] );
				else $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3], $vetor[4], $vetor[5], $vetor[6] );
			}
			else {
				for ($i = 0; $i < $qtdCondicoes; $i++) {
					$valores[count($valores)] = $vetor[(4 * ($i + 1)) - 1];
					if ( count($vetor) == (4 * ($i + 1)) ) $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . (count($valores) - 1) );
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "ordem" AND count($vetor) == (7 + ($i * 4)) ) $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . (count($valores) - 1), $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)], $vetor[6 + ($i * 4)] );
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "limite") $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . (count($valores) - 1), $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)] );
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "ordem") $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . (count($valores) - 1), $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)], $vetor[6 + ($i * 4)], $vetor[7 + ($i * 4)], $vetor[8 + ($i * 4)] );
					else $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . (count($valores) - 1), $vetor[4 * ($i + 1)] );
				}
			}
			$atualizacao = $this->CONEXAO->prepare($sql);
			for ($i = 0; $i < count($valores); $i++) {
				$atualizacao->bindParam( ':v' . $i, $valores[$i] );
			}
			$atualizacao->execute();
			// echo "Atualizado com sucesso!<br>";
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	public function atualizarTodos() { // tabela | campo | valor | ... 
		$vetor = func_get_args();
		$tabela = $vetor[0];
		$sql = "UPDATE $tabela SET";
		$condicao;
		$valores = [];

		try {
			$j = 1;
			while ($j + 2 < count($vetor)) {
				$sql = $sql . " " . $vetor[$j] . " = :v" . count($valores) . ",";
				$valores[count($valores)] = $vetor[$j + 1];
				$j += 2;
			}
			$sql = $sql . " " . $vetor[$j] . " = :v" . count($valores) . " WHERE 1";
			$valores[count($valores)] = $vetor[$j + 1];

			$atualizacao_total = $this->CONEXAO->prepare($sql);
			for ($i = 0; $i < count($valores); $i++) {
				$atualizacao_total->bindParam( ':v' . $i, $valores[$i] );
			}
			$atualizacao_total->execute();
			// echo "Atualizado com sucesso!<br>";
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	public function inserir() { // tabela | campo1 | campo2 | campo3 | ...
		$vetor = func_get_args(); $tabela = $vetor[0];
		$i; $data = 0;
		try {
			$sql = "INSERT INTO $tabela VALUES (";
			for ($i = 1; $i < count($vetor); $i++) {
				if ($vetor[$i] === "NOW()") $data = $i;
			}
			for ($i = 1; $i < count($vetor) - 1; $i++) {
				if ($i == $data) $sql .= "NOW(), ";
				else $sql .= ":v" . $i . ", ";
			}
			if ($i == $data) $sql .= "NOW())";
			else $sql .= ":v" . $i . ")";

			$insercao = $this->CONEXAO->prepare($sql);
			for ($i = 1; $i < count($vetor); $i++) {
				if ($i != $data) $insercao->bindParam(':v' . $i, $vetor[$i]);
			}
			$insercao->execute();
			// echo "Inserido com sucesso!<br>";
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	public function apagar() {
		// tabela | condição (de forma formal, incluindo aspas simples em strings)
		// tabela | condição | "ordem" | campo | ordem.
		// tabela | condição | "limite" | valor.
		// tabela | condição | "ordem" | campo | ordem | "limite" | valor.

		// tabela | campo | operação | valor.
		// tabela | campo | operação | valor | "ordem" | campo | ordem.
		// tabela | campo | operação | valor | "limite" | valor.
		// tabela | campo | operação | valor | "ordem" | campo | ordem | "limite" | valor.

		// tabela | campo | operação | valor | e/ou | campo | operação | valor.
		// tabela | campo | operação | valor | e/ou | campo | operação | valor | "ordem" | campo | ordem.
		// tabela | campo | operação | valor | e/ou | campo | operação | valor | "limite" | valor.
		// tabela | campo | operação | valor | e/ou | campo | operação | valor | "ordem" | campo | ordem | "limite" | valor
		$vetor = func_get_args(); $tabela = $vetor[0]; $sql = "DELETE FROM $tabela WHERE"; $condicao; $valores = []; $qtdCondicoes = $this->contarCondicoes($vetor);
		try {
			if (count($vetor) == 2 OR mb_strtolower($vetor[2]) === "ordem" OR mb_strtolower($vetor[2]) === "limite") {
				$separado = $this->separador($vetor[1], $valores);
				$condicao = $separado[0];
				$valores = $separado[1];
				if (count($vetor) == 2) $sql = $sql . " " . $this->condicao($condicao);
				else if (mb_strtolower($vetor[2]) === "ordem" AND count($vetor) == 5) $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3], $vetor[4] );
				else if (mb_strtolower($vetor[2]) === "limite") $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3] );
				else $sql = $sql . " " . $this->condicao($condicao, $vetor[2], $vetor[3], $vetor[4], $vetor[5], $vetor[6] );
			}
			else {
				for ($i = 0; $i < $qtdCondicoes; $i++) {
					$valores[$i] = $vetor[(4 * ($i + 1)) - 1];
					if ( count($vetor) == (4 * ($i + 1)) ) $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i);
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "ordem" AND count($vetor) == (7 + ($i * 4)) ) $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)], $vetor[6 + ($i * 4)] );
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "limite") $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)] );
					else if ( mb_strtolower($vetor[4 * ($i + 1)] ) === "ordem") $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 + ($i * 4)], $vetor[5 + ($i * 4)], $vetor[6 + ($i * 4)], $vetor[7 + ($i * 4)], $vetor[8 + ($i * 4)] );
					else $sql = $sql . " " . $this->condicao($vetor[1 + ($i * 4)], $vetor[2 + ($i * 4)], ":v" . $i, $vetor[4 * ($i + 1)] );
				}
			}
			$exclusao = $this->CONEXAO->prepare($sql);
			for ($i = 0; $i < count($valores); $i++) {
				$exclusao->bindParam( ':v' . $i, $valores[$i] );
			}
			$exclusao->execute();
			// echo "Apagado com sucesso!<br>";
		}
		catch (PDOException $e) {
			echo "Falha na conexão: " . $e->getMessage();
		}
	}

	private function contarCondicoes($vetor) {
		$num = 1;
		for ($i = 0; $i < count($vetor); $i++) {
			if (gettype($vetor[$i]) === "string" AND (mb_strtolower($vetor[$i]) === "e" OR mb_strtolower($vetor[$i]) === "and" OR $vetor[$i] === "&" OR $vetor[$i] === "&&" OR mb_strtolower($vetor[$i]) === "ou" OR mb_strtolower($vetor[$i]) === "or" OR $vetor[$i] === "|" OR $vetor[$i] === "||")) $num++;
		}
		return $num;
	}

	private function separador($texto, $valores) {
		$vetor = preg_split('//u', $texto, null, PREG_SPLIT_NO_EMPTY); $palavra = FALSE; $idv = count($valores); $novoTexto; $char; $charAnterior = ""; $valores[$idv] = "";

		for ($i = 0; $i < count($vetor); $i++) {
			$char = ord($vetor[$i]);

			if ($char == 39) { // ASPAS SIMPLES.
				if ($palavra == TRUE) {
					$valores[$idv] = $valores[$idv] . $vetor[$i];
					$palavra = FALSE; $idv++; $valores[$idv] = "";
				}
				else $palavra = TRUE;
			}
			if ( ($char > 47 AND $char < 58) OR $palavra == TRUE) $valores[$idv] = $valores[$idv] . $vetor[$i];
			else if ( ($char == 32 OR $char == 41) AND ($charAnterior > 47 AND $charAnterior < 58 AND $palavra == FALSE) ) {
				$idv++; $valores[$idv] = "";
			}
			$charAnterior = $char;
		}
		if ($valores[count($valores) - 1] === "") array_pop($valores);
		$novoTexto = $texto;
		for ($i = 0; $i < count($valores); $i++) {
			$novoTexto = preg_replace("/ $valores[$i]/u", " :v" . $i, $novoTexto, 1);
		}

		for ($i = 0; $i < count($valores); $i++) {
			$valores[$i] = preg_replace("/'/u", "", $valores[$i]);
		}

		$retorno = [$novoTexto, $valores];
		return $retorno;
	}

	private function condicao() {
		// condição.
		// condição | "ordem" | campo | ordem.
		// condição | "limite" | valor.
		// condição | "ordem" | campo | ordem | "limite" | valor.

		// campo | operação | valor.
		// campo | operação | valor | "ordem" | campo | ordem.
		// campo | operação | valor | "limite" | valor.
		// campo | operação | valor | "ordem" | campo | ordem | "limite" | valor.

		// campo | operação | valor | e/ou | campo | operação | valor.
		// campo | operação | valor | e/ou | campo | operação | valor | "ordem" | campo | ordem.
		// campo | operação | valor | e/ou | campo | operação | valor | "limite" | valor.
		// campo | operação | valor | e/ou | campo | operação | valor | "ordem" | campo | ordem | "limite" | valor.
		$retorno; $vetor = func_get_args();
		if (count($vetor) == 1) {
			$retorno = $vetor[0];
			return $retorno;
		}
		if (mb_strtolower($vetor[1]) === "ordem") {
			if (count($vetor) == 4) $retorno = $vetor[0] . " " . $this->ordem( $vetor[2], $vetor[3] );
			else $retorno = $vetor[0] . " " . $this->ordem( $vetor[2], $vetor[3] ) . " " . $this->limite( $vetor[5] );
			return $retorno;
		}
		if (mb_strtolower($vetor[1]) === "limite") {
			$retorno = $vetor[0] . " " . $this->limite( $vetor[2] );
			return $retorno;
		}
		$campo = $vetor[0]; $operador = $vetor[1]; $valor = $vetor[2];
		if (mb_strtolower($operador) === "igual" OR $operador === "=" OR $operador === "==") $retorno = "$campo = $valor";
		else if (mb_strtolower($operador) === "diferente" OR $operador === "!=" OR $operador === "<>") $retorno = "NOT $campo = $valor";
		else if (mb_strtolower($operador) === "maior" OR $operador === ">") $retorno = "$campo > $valor";
		else if (mb_strtolower($operador) === "menor" OR $operador === "<") $retorno = "$campo < $valor";
		else if (mb_strtolower($operador) === "maiorigual" OR mb_strtolower($operador) === "maiorouigual" OR $operador === ">=" OR $operador === "=>") $retorno = "$campo >= $valor";
		else if (mb_strtolower($operador) === "menorigual" OR mb_strtolower($operador) === "menorouigual" OR $operador === "<=" OR $operador === "=<") $retorno = "$campo <= $valor";
		else if (mb_strtolower($operador) === "dentrointervalo" OR mb_strtolower($operador) === "dentrodointervalo" OR mb_strtolower($operador) === "nointervalo" OR mb_strtolower($operador) === "intervalo" OR mb_strtolower($operador) === "entre" OR mb_strtolower($operador) === "dentro") $retorno = "$campo BETWEEN " . $valor[0] . " AND " . $valor[1];
		else if (mb_strtolower($operador) === "foraintervalo" OR mb_strtolower($operador) === "foradointervalo" OR mb_strtolower($operador) === "fora") $retorno = "$campo NOT BETWEEN " . $valor[0] . " AND " . $valor[1];
		else if (mb_strtolower($operador) === "comecacom") $retorno = "$campo LIKE '" . $valor . "%'";
		else if (mb_strtolower($operador) === "contem") $retorno = "$campo LIKE '%" . $valor . "%'";

		if (count($vetor) > 3) {
			if (mb_strtolower($vetor[3]) === "limite") $retorno = $retorno . " " . $this->limite( $vetor[4] );
			else if (mb_strtolower($vetor[3]) === "ordem" AND count($vetor) == 6) $retorno = $retorno . " " . $this->ordem( $vetor[4], $vetor[5] );
			else if (mb_strtolower($vetor[3]) === "ordem") $retorno = $retorno . " " . $this->ordem( $vetor[4], $vetor[5] ) . " " . $this->limite( $vetor[7] );
			else $retorno = $retorno . " " . $this->eou( $vetor[3] );
		}
		return $retorno;
	}

	private function eou($texto) {
		$retorno;
		if ( mb_strtolower($texto) === "ou" OR mb_strtolower($texto) === "or" ) $retorno = "OR";
		else $retorno = "AND";
		return $retorno;
	}

	private function ordem($campo, $ordem) {
		$retorno;
		if (mb_strtolower($ordem) === "decrescente") $retorno = "ORDER BY " . $campo . " DESC";
		else $retorno = "ORDER BY " . $campo . " ASC";
		return $retorno;
	}

	private function limite($valor) {
		$retorno = "LIMIT " . $valor;
		return $retorno;
	}

	private function reduzirVetor($vetor) {
		$retorno = []; $liberado = FALSE;
		for ($i = 0; $i < count($vetor); $i++) {
			if (mb_strtolower($vetor[$i]) === "quando") $liberado = TRUE;
			if ($liberado == TRUE) $retorno[count($retorno)] = $vetor[$i];
		}
		return $retorno;
	}

	private function eNumero($teste) {
		$soNumero = TRUE;
		$teste = preg_split('//u', $teste, null, PREG_SPLIT_NO_EMPTY);

		foreach ($teste as $char) {
			$char = ord($char);
			if ($char < 48 OR $char > 57) $soNumero = FALSE;
		}
		return $soNumero;
	}
}

$NOME_SERVIDOR = "localhost";
$NOME_USUARIO = "root";
$SENHA = "";
$BANCO_DE_DADOS = "card_game";

$c = new Conexao($NOME_SERVIDOR, $NOME_USUARIO, $SENHA, $BANCO_DE_DADOS);

// EXEMPLOS DE USO DA API:
// function main() {
	// $c = new Conexao("127.0.0.1", "root", "", "test");
	// $c->inserir("carrosnovos", null, "verme ambulante", 10, "nada");
	// $c->atualizar("carrosnovos", "velocidade", 222, "quando", "nome = 'ford'");
	// $c->apagar("carrosnovos", "velocidade = 4 and nome = 'vaca'");
	// $resposta = $c->buscar("carrosnovos", "true", "ordem", "velocidade", "decrescente", "limite", 10);
	// $qtd = $c->quantidade("carrosnovos", "nome", "==", "velho");
	// $c->desconectar();
	// echo "</br>O IP do usuário é: " . $c->IP;
	// echo "</br>As informações do usuário são: " . $c->INFO;
	// echo "</br>Número de respostas: " . $qtd;
// }

// main();