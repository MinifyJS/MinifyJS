<?php
class ReturnNode extends Node {
	protected $value;

	public function __construct(Expression $value) {
		$this->value = $value;

		$this->value->parent($this);

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

	public function isBreaking() {
		return true;
	}
}