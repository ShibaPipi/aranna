#### 代码格式化（养成优秀的代码风格，减少冲突，代码可读性更高）
- 代码的格式，选择 Laravel 风格
- 代码提交前一定要经过格式化 + 优化导入

#### 编码设计规范（养成封装的习惯，提高代码可读性）
- 短函数，单个函数不超过 80 行（重要），最好 40 行以内，一个函数实现一个功能，有明确的输入输出
- 函数命名必须有意义，尽最大可能使用动宾结构，加上必要的函数注释
- 常量：所有字母必须大写，词间以下划线分隔，比如：API_VERSION
- 属性，小驼峰，比如 $adminUser
- 类函数：小驼峰，如 getUser
- 不允许使用拼音命名，除非专有名词，比如人民币单位：yuan
- 尽可能使用已有的开源组件（正在维护的、活跃的、稳定的），不要重复造轮子
- 路由规范：保留原项目的规范，使用具体的接口语义，只使用 GET 和 POST，查询接口用 GET，写入接口用 POST
- 异常情况需要记录错误日志，以方便排查（尤其是第三方返回的接口，如支付回调）
- 响应格式规范化
    
    ```json
    Content-Type: application/json;charset=UTF-8
    {
        body
    }
    ```
    
    > body 的 json 内容：
    > 1. 正常情况
    
    ```json
    {
      "errno": xxx,
      "errmsg": xxx,
      "data": {}
    }
    ```
    > 2.失败异常情况
    ```json
    {
      errno: xxx,
      errmsg: xxx
    }
    ```

#### 数据库规范（具体可参考 58 到家数据库 30 条军规）
- 表名、字段名全使用蛇形命名，见名知意
- 表设计默认情况存在 id（主键），created_at（创建时间），updated_at（更新时间），deleted_at（软删除）四个字段
- 不使用存储过程、视图、触发器、Event
> 数据库最擅长的是数据持久化（存储数据），并不擅长做计算，因此要把计算放到程序中去实现，数据库制作自己最擅长的事情
- 禁止使用外键，如果有外键完整性约束，需要程序自行控制
> 外键会导致表与表之间的耦合，影响 SQL 性能，所以互联网项目一般不推荐使用外键
- 禁止使用 ENUM，可使用 TINYINT 代替之
> 使用枚举时，若想新增一个枚举，需要执行一个 DDL（表结构修改）的操作，数据量大的情况下可能会锁表，影响线上业务运行
- 数据库相关操作一定不能使用原生 SQL，要使用查询构造器进行语句的构建
> 原生 SQL 不容易管理，会对维护、重构造成很大的障碍

### 以上所有基础规范，虽然不多，但是能够做好也不容易，很多企业的项目根本做不到这些，导致项目原来越混乱、难维护，这是技术负责人的失职
#### 要养成良好的习惯，打好基础
