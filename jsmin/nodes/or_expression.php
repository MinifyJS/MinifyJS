<?php
class OrExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_OR, $left, $right);
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = parent::visit($ast, $parent);

		$else = $that->left->asBoolean();

		if ($else === true) {
			$that->right->gone();
			return $that->left;
		} elseif ($else === false) {
			$this->left->gone();
			return $that->right;
		}

		if ($that->right instanceof OrExpression) {
			$result = new OrExpression(
				new OrExpression($that->left, $that->right->left),
				$that->right->right
			);

			return $result->visit($ast, $parent);
		}

		// this will be inferred from the expression
		if ($that->actualType() === 'boolean') {
			/*
			 * This method will allow deep boolean expressions to be shorter:
		  	 * !!a || !!b || !!c
	  	  	 * !(!a && !b && !c)
	  	  	 * !!(a || b || c)
			 */
			return AST::bestOption(array(
				new NotExpression(new NotExpression($that->negate()->negate())),
				new NotExpression($that->negate()),
				$that
			));
		}

		return $that;
	}

	public function toString() {
		return $this->binary('||');
	}

	public function looseBoolean() {
		if (false === ($right = $this->right->asBoolean())) {
			return $this->left->looseBoolean();
		}

		$result = $this->negate()->negate();

		return !($result instanceof self) ? $result->looseBoolean() : $result;
	}

	public function negate() {
		return AST::bestOption(array(
			new AndExpression($this->left->negate(), $this->right->negate()),
			parent::negate()
		));
	}

	public function type() {
		if ((null !== ($type = $this->left->type())) && $type === $this->right->type()) {
			return $type;
		}

		return null;
	}

	public function optimize() {
		return AST::bestOption(array(
			$this->looseBoolean(),
			new AndExpression($this->left->negate()->looseBoolean(), $this->right)
		));
	}

	public function precedence() {
		return 4;
	}
}
