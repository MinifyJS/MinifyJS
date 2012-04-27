<?php
class FunctionNode extends Node {
	protected $name;
	protected $params;
	protected $body;
	protected $functionForm;

	public function __construct($name, array $params, ScriptNode $body, $form) {
		$this->name = $name;
		$this->params = $params;
		$this->body = $body;
		$this->functionForm = $form;

		parent::__construct();
	}

	public function visit(AST $ast) {
		if ($this->name) {
			$this->name = $this->name->visit($ast);
		}

		foreach($this->params as $i => $p) {
			$p->write();
			$this->params[$i] = $p->visit($ast);
		}

		$this->body = $this->body->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		if ($this->name) {
			$this->name->collectStatistics($ast);
		}

		foreach($this->params as $p) {
			$p->collectStatistics($ast, true);
		}

		$this->body->collectStatistics($ast);
	}

	public function toString() {
		if ($this->name && !$this->name->keep(1) && $this->functionForm !== EXPRESSED_FORM) {
			return '';
		}

		$space = AST::$options['beautify'] ? ' ' : '';

		return 'function' . ($this->name && ($this->functionForm !== EXPRESSED_FORM || $this->name->used() > 1) ? ' ' . $this->name->toString() : $space)
			. '(' . implode(',' . $space, $this->params) . ')' . $space
			. '{' . $this->body->asBlock()->toString(true) . '}';
	}

	public function onlyReturns() {
		$n = $this->body->isSingle();

		if ($n instanceof ReturnNode) {
			return $n->value();
		}

		return null;
	}

	public function debug() {
		return 'function ' . ($this->name ? $this->name->debug() : '') . '( ' . implode(',', $this->params) . ') ' . $this->body->asBlock()->toString(false);
	}
}
