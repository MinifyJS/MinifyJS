<?php
class ArrayExpression extends Expression {
	public function __construct(array $entries) {
		$this->nodes = $entries;

		parent::__construct();
	}

	public function visit(AST $ast) {
		foreach($this->nodes as $i => $e) {
			$this->nodes[$i] = $e->visit($ast);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $e) {
			$e->collectStatistics($ast);
		}
	}

	public function toString() {
		return '[' . implode(',', $this->nodes) . ']';
	}

	public function type() {
		return 'object';
	}

	public function precedence() {
		return 0;
	}

	public function asBoolean() {
		return true;
	}

	public function asNumber() {
		return 0;
	}
}
