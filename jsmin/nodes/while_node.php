<?php
class WhileNode extends Node {
	protected $condition;

	protected $body;

	public function __construct(Expression $cond, Node $body) {
		$this->condition = $cond;
		$this->body = $body;

		$this->condition->parent($this);
		$this->body->parent($this);
	}

	public function visit(AST $ast) {
		$this->condition = $this->condition->visit($ast);
		$this->body = $this->body->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->condition->collectStatistics($ast);
		$this->body->collectStatistics($ast);
	}

	public function last() {
		return $this->body->last();
	}

	public function toString() {
		return 'while(' . $this->condition->toString() . ')' . $this->body->asBlock()->toString(null, true);
	}
}