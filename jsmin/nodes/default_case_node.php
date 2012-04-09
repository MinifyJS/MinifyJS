<?php
class DefaultCaseNode extends Node {
	public function __construct(Node $stmt) {
		$this->stmt = $stmt;
	}

	public function visit(AST $ast) {
		$this->stmt = $this->stmt->visit($ast);

		return $this;
	}

	public function toString() {
		return 'default:' . $this->stmt->toString();
	}

	public function collectStatistics(AST $ast) {
		$this->stmt->collectStatistics($ast);
	}
}