# diffTables

SQL的建表语句的比较，并生成ALTER TABLE 的语句

比较的原则：
1. 左表为基准， 右表为目标
3. 改表的语句达成从左表变成右表的效果
4. 左右表同名列被视为需要修改列类型
5. 左右表不同名列被视为需要增加或删除的列
6. 如果左右均为多表SQL文件，则为右侧不存在的表增加 DROP table 语句，为左侧不存在的表保留 create table 语句
2. 左右表表名不同时会在最后增加修改表名的语句



