<?php
class CommaExpression extends Expression {
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

	public function nodes() {
		return $this->nodes;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $e) {
			$e->collectStatistics($ast);
		}
	}

	public function toString() {
		return implode(',', $this->nodes);
	}

	public function asBoolean() {
		return $this->represents()->asBoolean();
	}

	public function asString() {
		return $this->represents()->asString();
	}

	public function asNumber() {
		return $this->represents()->asNumber();
	}

	public function type() {
		return $this->represents()->type();
	}

	public function represents() {
		return end($this->nodes);
	}

	public function precedence() {
		return 1;
	}
}
