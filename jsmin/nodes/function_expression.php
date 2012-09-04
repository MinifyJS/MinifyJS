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

	public function gone() {
		$this->middle->gone();
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

	public function removeUseless() {
		return new VoidExpression(new Number(0));
	}

	public function optimizeArguments() {
		$this->middle->optimizeArguments();
	}

	public function countLetters(&$letters) {
		$this->middle->countLetters($letters);
	}

	public function debug () {
		return '(' . $this->middle->debug() . ')';
	}
}
