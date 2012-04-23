<?php
class FunctionExpression extends Expression {
	public function __construct(FunctionNode $n) {
		$this->middle = $n;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->middle = $this->middle->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->middle->collectStatistics($ast);
	}

	public function toString() {
		return $this->middle->toString();
	}

	public function type() {
		return 'function';
	}

	public function precedence() {
		return 0;
	}

	public function onlyReturns() {
		return $this->middle->onlyReturns();
	}

	public function debug () {
		return '(' . $this->middle->debug() . ')';
	}
}