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

	public function gone() {
		$this->label->gone();
		$this->stmt->gone();
	}

	public function toString() {
		if (AST::$options['beautify']) {
			return 'case ' . $this->label->toString() . ':'
				. $this->stmt->asBlock()->toString(true);
		}

		return 'case' . Stream::legalStart($this->label->toString()) . ':' . $this->stmt->asBlock()->toString(true);
	}

	public function countLetters(&$letters) {
		$letters['c'] += 1;
		$letters['a'] += 1;
		$letters['s'] += 1;
		$letters['e'] += 1;

		$this->label->countLetters($letters);
		$this->stmt->countLetters($letters);
	}


	public function collectStatistics(AST $ast) {
		$this->label->collectStatistics($ast);
		$this->stmt->collectStatistics($ast);
	}

	public function removeBreak() {
		$this->stmt = $this->stmt->asBlock()->removeBreak();
	}
}
