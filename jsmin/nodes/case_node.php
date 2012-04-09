<?php
class CaseNode extends Node {
	protected $label;
	protected $stmt;

	public function __construct(Expression $label, Node $stmt) {
		$this->label = $label;
		$this->stmt = $stmt;
	}

	public function visit(AST $ast) {
		$old = $this->label;
		$this->label = $this->label->visit($ast);
		$this->stmt = $this->stmt->visit($ast);

		return $this;
	}

	public function toString() {
		return 'case' . Stream::legalStart($this->label->toString()) . ':' . $this->stmt->toString();
	}

	public function collectStatistics(AST $ast) {
		$this->label->collectStatistics($ast);
		$this->stmt->collectStatistics($ast);
	}
}