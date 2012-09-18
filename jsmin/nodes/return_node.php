<?php
class ReturnNode extends Node {
	protected $value;

	public function __construct(Expression $value) {
		$this->value = $value;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->value = $this->value->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		if ($this->value) {
			$this->value->collectStatistics($ast);
		}
	}

	public function value() {
		return $this->value;
	}

	public function gone() {
		if ($this->value) {
			$this->value->gone();
		}
	}

	public function last() {
		return $this;
	}

	public function remove(Node $n) {
		$this->value = new VoidExpression(new Number(0));
	}

	public function toString() {
		if ($this->value->isVoid()) {
			return 'return';
		}

		if (AST::$options['beautify']) {
			return 'return ' . $this->value->toString();
		}

		return 'return' . Stream::legalStart($this->value);
	}

	public function countLetters(&$letters) {
		foreach(array('r', 'e', 't', 'u', 'r', 'n') as $l) {
			$letters[$l] += 1;
		}

		if (!$this->value->isVoid()) {
			$this->value->countLetters($letters);
		}
	}

	public function moveExpression(Expression $x) {
		$this->value = new CommaExpression(array_merge($x->nodes(), $this->value->nodes()));
		return true;
	}

	public function isBreaking() {
		return true;
	}
}