<?php
class DecrementExpression extends UnaryExpression {
	protected $postfix;

	public function __construct(Expression $left, $postfix) {
		parent::__construct($left);

		$this->postfix = $postfix;
	}

	public function toString() {
		if ($this->postfix) {
			return $this->group($this, $this->left, false) . '--';
		} else {
			return '--' . $this->group($this, $this->left);
		}
	}

	public function gone() {
		$this->left->gone();
		$this->left->unassign();
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast, true);
	}

	public function type() {
		return $this->left->type();
	}

	public function precedence() {
		return 15;
	}

	public function isConstant() {
		return false;
	}

	public function optimize() {
		return new DecrementExpression($this->left, false);
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
	}


	public function removeUseless() {
		return new DecrementExpression($this->left->removeUseless(), $this->postfix);
	}
}