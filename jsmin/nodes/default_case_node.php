<?php
class DefaultCaseNode extends CaseNode {
	public function __construct(Node $stmt) {
		$this->stmt = $stmt;
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->stmt = $this->stmt->visit($ast, $this);

		return $this;
	}

	public function gone() {
		$this->stmt->gone();
	}

	public function toString() {
		return 'default:' . $this->stmt->asBlock()->toString(true);
	}

	public function countLetters(&$letters) {
		$letters['d'] += 1;
		$letters['e'] += 1;
		$letters['f'] += 1;
		$letters['a'] += 1;
		$letters['u'] += 1;
		$letters['l'] += 1;
		$letters['t'] += 1;

		$this->stmt->countLetters($letters);
	}

	public function collectStatistics(AST $ast) {
		$this->stmt->collectStatistics($ast);
	}
}