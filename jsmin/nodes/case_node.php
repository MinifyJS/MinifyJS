<?php
class CaseNode extends Node {
	protected $label;
	protected $stmt;

	public function __construct(Expression $label, Node $stmt) {
		$this->label = $label;
		$this->stmt = $stmt;
	}

	public function visit(AST $ast, Node $parent = null) {
		return new CaseNode(
			$this->label->visit($ast, $this),
			$this->stmt->visit($ast, $this)
		);
	}

	public function stmt() {
		return $this->stmt;
	}

	public function label() {
		return $this->label;
	}

	public function gone() {
		$this->label->gone();
		$this->stmt->gone();
	}

	public function toString() {
		$block = $this->stmt->asBlock()->toString(true);

		if ($block === ';'|| $block === ";\0") {
			$block = '';
		}

		if (AST::$options['beautify']) {
			return 'case ' . $this->label->toString() . ':' . $block;
		}

		return 'case' . Stream::legalStart($this->label->toString()) . ':' . $block;
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
