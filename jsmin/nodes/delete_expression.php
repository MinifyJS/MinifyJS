<?php
class DeleteExpression extends Expression {
	public function __construct(Expression $left) {
		$this->left = $left;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast, true);
	}

	public function value() {
		return null;
	}

	public function toString() {
		return 'delete' . Stream::legalStart($this->group($this, $this->left, false));
	}

	public function isConstant() {
		return false;
	}

	public function gone() {
		$this->left->gone();
		$this->left->unassign();
	}

	public function type() {
		return 'boolean';
	}

	public function countLetters(&$letters) {
		foreach(array('d', 'e', 'l', 'e', 't', 'e') as $l) {
			$letters[$l] += 1;
		}

		$this->left->countLetters($letters);
	}

	public function hasSideEffects() {
		return true;
	}

	public function precedence() {
		return 14;
	}
}
