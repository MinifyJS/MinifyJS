<?php
class IncrementExpression extends UnaryExpression {
	protected $postfix;

	public function __construct(Expression $left, $postfix) {
		parent::__construct($left);

		$this->postfix = $postfix;
	}

	public function toString() {
		if ($this->postfix) {
			return $this->left->toString() . '++';
		} else {
			return '++' . $this->left->toString();
		}
	}

	public function type() {
		return $this->left->type();
	}

	public function precedence() {
		return 15;
	}
}