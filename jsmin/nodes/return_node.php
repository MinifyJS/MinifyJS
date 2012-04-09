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

	public function remove(Node $n) {
		$this->value = new VoidExpression(new Number(0));
	}

	public function toString() {
		return 'return' . (!($this->value instanceof VoidExpression && $this->value->isEmpty()) ? Stream::legalStart($this->value) : '');
	}
}