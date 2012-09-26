<?php
class LabelNode extends Node {
	protected $label;
	protected $stmt;

	public function __construct(Identifier $label, Node $stmt) {
		$this->label = $label;
		$this->stmt = $stmt;
	}

	public function stmt() {
		return $this->stmt;
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->stmt = $this->stmt->visit($ast, $parent);

		if ($ast->hasStats() && !$this->label->used()) {
			return $this->stmt;
		}

		return $this;
	}

	public function countLetters(&$letters) {
		$this->stmt->countLetters($letters);
	}

	public function toString() {
		return $this->label->toString() . ':' . $this->stmt->toString();
	}

	public function hasSideEffects() {
		return $this->stmt->hasSideEffects();
	}

	public function collectStatistics(AST $ast) {
		$this->stmt->collectStatistics($ast);
	}
}
