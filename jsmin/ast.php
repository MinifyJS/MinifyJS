<?php
error_reporting(E_ALL|E_STRICT);

spl_autoload_register(array('AST', 'load'));

class AST {
	protected $tree;
	protected $scope;

	protected $rootScope;

	public static $finalize = false;

	protected $reports = array();

	public static $options = array(
		'crush-bool' => true,
		'mangle' => true,
		'unsafe' => true,
		'strip-console' => false
	);

	protected $binaryClasses = array(
		OP_PLUS => 'Plus',
		OP_MINUS => 'Minus',
		OP_MUL => 'Mul',
		OP_AND => 'And',
		OP_DIV => 'Div'
	);

	public static function load($class) {
		$root = dirname(__FILE__) . '/';
		$options = array(strtolower(preg_replace('~([a-zA-Z])(?=[A-Z][a-z])~', '$1_', $class)), strtolower($class));

		foreach($options as $file) {
			foreach(array('', 'nodes/') as $dir) {
				if (is_file($root . $dir . $file . '.php')) {
					require $root . $dir . $file . '.php';
					return;
				}
			}
		}
	}

	public function __construct(JSNode $root) {
		$this->rootScope = $this->enter();
		$this->tree = $this->generate($root);
		$this->leave();
	}

	public function squeeze() {
		$this->tree->visit($this);

		$this->tree->collectStatistics($this);

		if (AST::$options['mangle']) {
			$this->rootScope->optimize();
		}

		self::$finalize = true;
	}

	public function report($rep = null) {
		if ($rep !== null) {
			$this->reports[] = $rep;
		}

		return $this->reports;
	}

	public function tree() {
		return $this->tree;
	}

	protected function enter() {
		return $this->scope = new Scope($this, $this->scope);
	}

	protected function leave() {
		return $this->scope = $this->scope->parent();
	}

	protected function generate($n = null, $dotBase = true, $asArray = true) {
		if ($n === null) {
			return new VoidExpression(new Number(0));
		}

		if (!($n instanceof JSNode)) {
			return $n;
		}

		switch($n->type) {
		case JS_SCRIPT:
			foreach($n->context->funDecls as $fun) {
				$this->scope->find($fun->name, true);
			}

			foreach($n->context->varDecls as $var) {
				$this->scope->find($var, true);
			}

			return new ScriptNode($this->nodeList($n->nodes));
		case JS_BLOCK:
			if (count($n->nodes) === 1) {
				return $this->generate($n->nodes[0]);
			}

			return new BlockStatement($this->nodeList($n->nodes));
		case JS_FOR_IN:
			$it = $this->generate($n->varDecl ? $n->varDecl : $n->iterator);

			return new ForInNode(
				$n->varDecl ? $it[0] : $it,
				$this->generate($n->object),
				$this->block($n->body)
			);
		case KEYWORD_RETURN:
			return new ReturnNode(
				$this->generate($n->value)
			);
		case KEYWORD_FOR:
			return new ForNode(
				$this->generate($n->setup, true, false),
				$this->generate($n->condition),
				$this->generate($n->update),
				$this->block($n->body)
			);
		case OP_SEMICOLON:
			if ($n->expression) {
				return $this->generate($n->expression);
			}

			return new VoidExpression(new Number(0));
		case JS_GROUP:
			return $this->generate($n->nodes[0]);
		case JS_CALL:
			return new CallExpression(
				$this->generate($n->nodes[0]),
				$this->generate($n->nodes[1])
			);
		case JS_LABEL:
			return new LabelNode(new Identifier(null, $n->label), $this->generate($n->statement));
		case KEYWORD_FUNCTION:
			if ($n->functionForm === EXPRESSED_FORM) {
				$this->enter();
			}

			$ident = $n->name === null ? null : new IdentifierExpression($this->scope->find($n->name, true));

			if ($n->functionForm !== EXPRESSED_FORM) {
				$this->enter();
			}

			$f = new FunctionNode(
				$ident,
				$this->identifierList($n->params),
				$this->generate($n->body)
			);

			$this->leave();

			if ($n->functionForm === EXPRESSED_FORM) {
				return new FunctionExpression($f);
			}

			return $f;
		case KEYWORD_VAR:
			$l = array();
			foreach($n->nodes as $x) {
				$l[] = new VarNode(
					new IdentifierExpression($this->scope->find($x->name, true)),
					$this->generate($x->initializer)
				);
			}

			if (!$asArray) {
				return new VarDeclarationsNode($l);
			}

			return $l;
		case OP_DOT:
			return new DotExpression(
				$this->generate($n->nodes[0], $dotBase),
				$this->generate($n->nodes[1], false)
			);
		case KEYWORD_TYPEOF:
			return new TypeofExpression(
				$this->generate($n->nodes[0])
			);
		case KEYWORD_DELETE:
			return new DeleteExpression(
				$this->generate($n->nodes[0])
			);
		case KEYWORD_THROW:
			return new ThrowNode(
				$this->generate($n->exception)
			);
		case KEYWORD_INSTANCEOF:
			return new InstanceofExpression(
				$this->generate($n->nodes[0]),
				$this->generate($n->nodes[1])
			);
		case TOKEN_IDENTIFIER:
			if ($dotBase) {
				return new IdentifierExpression($this->scope->find($n->value));
			} else {
				return new Identifier(null, $n->value);
			}
		case KEYWORD_IF:
			return new IfNode(
				$this->generate($n->condition),
				$this->block($n->thenPart),
				$n->elsePart ? $this->block($n->elsePart) : null
			);
		case KEYWORD_VOID:
			return new VoidExpression(
				$this->generate($n->nodes[0])
			);
		case TOKEN_NUMBER:
			return new Number($n->value);
		case KEYWORD_TRUE:
		case KEYWORD_FALSE:
			return new Boolean($n->type === KEYWORD_TRUE ? true : false);
		case TOKEN_REGEXP:
			return new RegExp($n->value);
		case TOKEN_STRING:
			return new String($n->value);
		case KEYWORD_NULL:
			return new Nil();
		case KEYWORD_WHILE:
			return new WhileNode($this->generate($n->condition), $this->generate($n->body));
		case KEYWORD_DO:
			return new DoWhileNode($this->generate($n->condition), $this->generate($n->body));
		case OP_OR:
			return new OrExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_AND:
			return new AndExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_XOR:
			return new BitwiseXorExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_OR:
			return new BitwiseOrExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_AND:
			return new BitwiseAndExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_BITWISE_NOT:
			return new BitwiseNotExpression($this->generate($n->nodes[0]));
		case OP_EQ:
		case OP_STRICT_EQ:
			return new EqualExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]), $n->type === OP_STRICT_EQ);
		case OP_NE:
		case OP_STRICT_NE:
			return new NotEqualExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]), $n->type === OP_STRICT_NE);
		case OP_LT:
		case OP_LE:
		case OP_GE:
		case OP_GT:
			return new ComparisonExpression($n->type, $this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_MUL:
			return new MulExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_DIV:
			return new DivExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_MOD:
			return new ModExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_PLUS:
			return new PlusExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_MINUS:
			return new MinusExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_LSH:
		case OP_RSH:
		case OP_URSH:
			return new BitwiseShiftExpression($n->type, $this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case OP_UNARY_PLUS:
			return new UnaryPlusExpression($this->generate($n->nodes[0]));
		case OP_UNARY_MINUS:
			return new UnaryMinusExpression($this->generate($n->nodes[0]));
		case OP_INCREMENT:
			return new IncrementExpression($this->generate($n->nodes[0]), !!$n->postfix);
		case OP_DECREMENT:
			return new DecrementExpression($this->generate($n->nodes[0]), !!$n->postfix);
		case OP_NOT:
			return new NotExpression($this->generate($n->nodes[0]));
		case OP_HOOK:
			return new HookExpression(
				$this->generate($n->nodes[0]),
				$this->generate($n->nodes[1]),
				$this->generate($n->nodes[2])
			);
		case KEYWORD_IN:
			return new InExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case JS_INDEX:
			return new IndexExpression($this->generate($n->nodes[0]), $this->generate($n->nodes[1]));
		case JS_ARRAY_INIT:
			return new ArrayExpression($this->nodeList($n->nodes));
		case KEYWORD_THIS:
			return new This();
		case KEYWORD_NEW:
		case JS_NEW_WITH_ARGS:
			return new NewExpression(
				$this->generate($n->nodes[0]),

				$this->nodeList(isset($n->nodes[1]) ? $n->nodes[1]->nodes : array())
			);
		case OP_ASSIGN:
			return new AssignExpression(
				$n->value,

				$this->generate($n->nodes[0]),
				$this->generate($n->nodes[1])
			);
		case JS_OBJECT_INIT:
			$list = array();
			foreach($n->nodes as $x) {
				$list[] = new Property($this->generate($x->nodes[0], false), $this->generate($x->nodes[1]));
			}
			return new ObjectExpression($list);
		case KEYWORD_BREAK:
			return new BreakNode($n->label ? $this->generate($n->label, false) : null);
		case KEYWORD_CONTINUE:
			return new ContinueNode($n->label ? $this->generate($n->label, false) : null);
		case JS_LIST:
			return $this->nodeList($n->nodes);
		case OP_COMMA:
			return new CommaExpression($this->nodeList($n->nodes));
		case KEYWORD_TRY:
			return new TryNode(
				$this->block($n->tryBlock),
				$n->catchClause ? $this->generate($n->catchClause) : null,
				$n->finallyBlock ? $this->block($n->finallyBlock) : null
			);
		case KEYWORD_CATCH:
			return new CatchNode(
				$this->scope->find($n->varName, true),
				$this->generate($n->block)
			);
		case KEYWORD_SWITCH:
			return new SwitchNode(
				$this->generate($n->discriminant),
				$this->nodeList($n->cases)
			);
		case KEYWORD_CASE:
			return new CaseNode(
				$this->generate($n->label),
				$this->generate($n->statements)
			);
		case KEYWORD_DEFAULT:
			return new DefaultCaseNode(
				$this->generate($n->statements)
			);
		}

		throw new RuntimeException('Unknown handler for ' . $n->type);
	}

	protected function nodeList(array $l) {
		$n = array();

		foreach($l as $x) {
			$q = $this->generate($x);
			if (is_array($q)) {
				foreach($q as $a) {
					if ($a) {
						$n[] = $a;
					}
				}
			} else {
				if ($q) {
					$n[] = $q;
				}
			}
		}

		return $n;
	}

	protected function identifierList(array $list) {
		$n = array();

		foreach($list as $x) {
			$n[] = new IdentifierExpression($this->scope->find($x, true));
		}

		return $n;
	}

	protected function block(JSNode $n) {
		return new BlockStatement($this->nodeList($n->type !== JS_BLOCK ? array($n) : $n->nodes));
	}

	public static function bestOption($options) {
		if (!is_array($options)) {
			return $options;
		}

		if (($count = count($options)) === 1) {
			return $options[0];
		}

		if ($count === 2) {
			return strlen($options[0]) <= strlen($options[1]) ? $options[0] : $options[1];
		}

		return self::bestOption(array($options[0], self::bestOption(array_slice($options, 1))));
	}

}




