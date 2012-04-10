<?php
class VarNode extends Node {
	protected $name;
	protected $initializer;

	public function __construct(IdentifierExpression $i, Expression $init = null) {
		$this->name = $i;
		$this->initializer = $init;

		$this->name->parent($this);

		if ($this->initializer) {
			$this->initializer->parent($this);
		}

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->name = $this->name->visit($ast);

		if ($this->initializer) {
			$this->initializer = $this->initializer->visit($ast);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->name->collectStatistics($ast);

		if ($this->initializer) {
			$this->initializer->collectStatistics($ast);
		}
	}

	public function toString() {
		$init = $this->initializer;
		if (!$this->name->used() && (!$init || $init->isRedundant())) {
			return '';
		}

		return 'var ' . $this->name . ($init && !$init->isVoid() ? '=' . $this->initializer : '');
	}
}