//1. CommonJS规范 规定，`每个模块内部`，module变量代表当前模块
function Module(id, parent) {
  this.id = id;
  // 可知 通过 module.exports 默认导出的是一个空对象
  this.exports = {};
  this.parent = parent;
  if (parent && parent.children) {
    parent.children.push(this);
  }

  this.filename = null;
  this.loaded = false;
  this.children = [];
}



// 1. module.js 
// Module.prototype.require   原型方法
// 之所以在每个模块中都能使用 require() 函数是因为 require 函数定义在了每个模块中
Module.prototype.require = function(path) {
  assert(path, 'missing path');
  assert(typeof path === 'string', 'path must be a string');
  return Module._load(path, this, /* isMain */ false);
};


// 2. module.js
// Check the cache for the requested file.
// 1. If a module already exists in the cache: return its exports object.
// 2. If the module is native: call `NativeModule.require()` with the
//    filename and return the result.
// 3. Otherwise, create a new module for the file and save it to the cache.
//    Then have it load  the file contents before returning its exports
//    object.
// 该方法做了 5 件事：
// 1. 检查 Module._cache 中是否有缓存的模块实例
// 2. 如果缓存中没有，那么创建一个 Moudle 实例
// 3. 将创建的 Module 实例保存到缓存中，供下次使用。
// 4. 调用 module.load() 读取模块内容，然后调用 module.compile() 编译执行（封装成一个沙箱）该模块
//   - 如果加载解析出错，那么从缓存中删除该模块
// 5. 返回 module.exports 
Module._load = function(request, parent, isMain) {
  if (parent) {
    debug('Module._load REQUEST %s parent: %s', request, parent.id);
  }

  var filename = Module._resolveFilename(request, parent, isMain);

  //2.1 取缓存 看是否有值?
  var cachedModule = Module._cache[filename];
  if (cachedModule) {
    // 如果有值,导出
    return cachedModule.exports;
  }

  if (NativeModule.nonInternalExists(filename)) {
    debug('load native module %s', request);
    return NativeModule.require(filename);
  }

  //2.2 如果没有 创建一个模块（Module对象）
  var module = new Module(filename, parent);

  if (isMain) {
    process.mainModule = module;
    module.id = '.';
  }

  //2.3 创建后的 module 保存在缓存中
  Module._cache[filename] = module;

  // 加载编译
  tryModuleLoad(module, filename);
  
   //2.4 导出
  return module.exports;
 
};



// 3. module.js
function tryModuleLoad(module, filename) {
  var threw = true;
  try {
    //编译成功
    module.load(filename);
    threw = false;
  } finally {
    // 编译失败  从缓存中删除
    if (threw) {
      delete Module._cache[filename];
    }
  }
}


// 4. module.js
// Given a file name, pass it to the proper extension handler.
// 该模块中对要加载的模块进行编译
Module.prototype.load = function(filename) {
  debug('load %j for module %j', filename, this.id);

  assert(!this.loaded);
  this.filename = filename;
  this.paths = Module._nodeModulePaths(path.dirname(filename));

  var extension = path.extname(filename) || '.js';
  if (!Module._extensions[extension]) extension = '.js';
  Module._extensions[extension](this, filename); // 此行代码对被加载的模块实现了编译
  this.loaded = true;
};



// 5. module.js （编译）
// Native extension for .js
Module._extensions['.js'] = function(module, filename) {
  //5.1 说明 require() 加载模块是同步执行的
  var content = fs.readFileSync(filename, 'utf8');
  
  module._compile(internalModule.stripBOM(content), filename);
};


// 6. module.js
// Run the file contents in the correct scope or sandbox. Expose
// the correct helper variables (require, module, exports) to
// the file.
// Returns exception, if any.
// 编译（用一个沙箱来包装）执行该模块
Module.prototype._compile = function(content, filename) {
  // Remove shebang
  var contLen = content.length;
  if (contLen >= 2) {
    if (content.charCodeAt(0) === 35/*#*/ &&
        content.charCodeAt(1) === 33/*!*/) {
      if (contLen === 2) {
        // Exact match
        content = '';
      } else {
        // Find end of shebang line and slice it off
        var i = 2;
        for (; i < contLen; ++i) {
          var code = content.charCodeAt(i);
          if (code === 10/*\n*/ || code === 13/*\r*/)
            break;
        }
        if (i === contLen)
          content = '';
        else {
          // Note that this actually includes the newline character(s) in the
          // new output. This duplicates the behavior of the regular expression
          // that was previously used to replace the shebang line
          content = content.slice(i);
        }
      }
    }
  }

  // create wrapper function
  var wrapper = Module.wrap(content);

  // 执行模块
  var compiledWrapper = vm.runInThisContext(wrapper, {
    filename: filename,
    lineOffset: 0,
    displayErrors: true
  });

  if (process._debugWaitConnect && process._eval == null) {
    if (!resolvedArgv) {
      // we enter the repl if we're not given a filename argument.
      if (process.argv[1]) {
        resolvedArgv = Module._resolveFilename(process.argv[1], null);
      } else {
        resolvedArgv = 'repl';
      }
    }

    // Set breakpoint on module start
    if (filename === resolvedArgv) {
      delete process._debugWaitConnect;
      const Debug = vm.runInDebugContext('Debug');
      Debug.setBreakPoint(compiledWrapper, 0, 0);
    }
  }
  var dirname = path.dirname(filename);
  var require = internalModule.makeRequireFunction.call(this);
  var args = [this.exports, require, this, filename, dirname];
  var depth = internalModule.requireDepth;
  if (depth === 0) stat.cache = new Map();
  var result = compiledWrapper.apply(this.exports, args);
  if (depth === 0) stat.cache = null;
  return result;
};