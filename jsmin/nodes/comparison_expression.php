<?php
class ComparisonExpression extends BinaryExpression {
	const STRICT = true;
	const NOT_STRICT = false;

	static protected $reverseMap = array(
		OP_LE => OP_GT,
		OP_LT => OP_GE,
	);

	public function visit(AST $ast, Node $parent = null) {
		$left = $this->left->visit($ast, $this);
		$right = $this->right->visit($ast, $this);

		if (AST::$options['unsafe'] && isset(self::$reverseMap[$this->type])) {
			return new ComparisonExpression(self::$reverseMap[$this->type], $right, $left);
		}

		$that = new ComparisonExpression($this->type, $left, $right);

		return $that;
	}

	public function isConstant() {
		return $this->left->isConstant() && $this->right->isConstant();
	}

	public function toString() {
		return $this->binary($this->type);
	}

	public function type() {
		return 'boolean';
	}

	public function precedence() {
		return 10;
	}

	public function negate() {
		$options = array(parent::negate());

		if (AST::$options['unsafe'] && isset(self::$reverseMap[$this->type])) {
			$options[] = new ComparisonExpression(self::$reverseMap[$this->type], $this->left, $this->right);
		}

		return AST::bestOption($options);
	}
}
