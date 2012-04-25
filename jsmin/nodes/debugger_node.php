<?php
class DebuggerNode extends Node {
	public function visit(AST $ast) {
		if (AST::$options['strip-debug']) {
			return new Number(0);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {}

	public function toString() {
		return 'debugger';
	}
}
