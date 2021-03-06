<?php
class VoidExpression extends Expression {
	public function __construct(Expression $l) {
		$this->left = $l;
		parent::__construct();
	}

	public static function nil() {
		return new VoidExpression(new Number(0));
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);

		if ($this->isEmpty() && ($undef = $ast->visitScope()->find('undefined')) && $undef->isLocal()) {
			return new IdentifierExpression($undef);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function isEmpty() {
		return $this->left->isConstant();
	}

	public function isVoid() {
		return $this->isEmpty();
	}

	public function asBoolean() {
		if ($this->isConstant()) {
			return false;
		}
	}

	public function mayInline() {
		return $this->isConstant();
	}

	public function toString() {
		return 'void' . Stream::legalStart($this->group($this, $this->left));
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function gone() {
		$this->left->gone();
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects();
	}

	public function type() {
		return 'undefined';
	}

	public function precedence() {
		return 14;
	}

	public function countLetters(&$letters) {
		foreach(array('v', 'o', 'i', 'd') as $l) {
			$letters[$l] += 1;
		}

		if (!$this->left->isVoid()) {
			$this->left->countLetters($letters);
		}
	}

	public function removeUseless() {
		return $this->left->removeUseless();
	}
}