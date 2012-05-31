<?php
class PlusExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_PLUS, $left, $right);
	}

	public function visit(AST $ast) {
		$that = parent::visit($ast);

		if ($that->left->type() === 'string' || $that->right->type() === 'string') {
			if ((null !== $left = $that->left->asString()) && (null !== $right = $that->right->asString())) {
				return new String($left . $right, false);
			}
		}

		if ($that->left instanceof PlusExpression
				&& $that->left->right()->type() === 'string'
				&& (null !== $right = $that->right()->asString())) {
			if (null !== $left = $that->left->right()->asString()) {
				$result = new PlusExpression($that->left->left(), new String($left . $right, false));
				return $result->visit($ast);
			}
		}

		if (null !== $result = $this->asNumber()) {
			return AST::bestOption(array(new Number($result), $that));
		}

		return $that;
	}

	public function asNumber() {
		if ($this->left->type() === 'number' && $this->right->type() === 'number') {
			if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
				$expr = new Number(bcadd($left, $right, 100));
				return $expr->asNumber();
			}
		}
	}

	public function toString() {
		if (AST::$options['beautify']) {
			return $this->binary('+');
		}

		$r = $this->group($this, $this->right, false);
		if ($r[0] === '+') {
			$r = ' ' . $r;
		}

		return $this->group($this, $this->left) . '+' . $r;
	}

	public function isConstant() {
		return $this->left()->isConstant() && $this->right()->isConstant();
	}

	public function type() {
		if ($this->left()->type() === 'string' || $this->right->type() === 'string') {
			return 'string';
		}
	}

	public function precedence() {
		return 12;
	}
}
