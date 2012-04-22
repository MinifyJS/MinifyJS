<?php
class SwitchNode extends Node {
	protected $discriminant;
	protected $cases = array();

	public function __construct(Expression $discriminant, array $cases) {
		$this->discriminant = $discriminant;
		$this->cases = $cases;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->discriminant = $this->discriminant->visit($ast);

		foreach($this->cases as $i => $e) {
			$this->cases[$i] = $e->visit($ast);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->discriminant->collectStatistics($ast);

		foreach($this->cases as $i => $e) {
			$e->collectStatistics($ast);
		}
	}

	public function toString() {
		$result = 'switch(' . $this->discriminant->toString() . ')';
		if (AST::$options['beautify']) {
			$result .= ' {' . "\n";
		} else {
			$result .= '{';
		}

		$semi = '';

		foreach($this->cases as $case) {
			$result .= $semi . $case->toString();
			$semi = substr(rtrim($result), -1) === ';' ? '' : ';';
		}

		$result .= '}';

		return $result;
	}
}