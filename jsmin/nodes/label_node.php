<?php
class LabelNode extends Node {
	protected $label;
	protected $stmt;

	public function __construct(Identifier $label, Node $stmt) {
		$this->label = $label;
		$this->stmt = $stmt;
	}

	public function visit(AST $ast) {
		$this->stmt = $this->stmt->visit($ast);

		return $this;
	}

	public function toString() {
		return $this->label->toString() . ':' . $this->stmt->toString();
	}

	public function collectStatistics(AST $ast) {
		$this->stmt->collectStatistics($ast);
	}
}
