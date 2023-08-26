# Движок структурного автомата
 Структурным автоматом назовём конечный автомат с несколькими активными состояниями. 
<p> Порядок установки и работы:
<p>1. Установить на http-сервере поддержку sqlite и поддержку php- и python- скриптов.
<p>2. Поместить все файлы из этого репозитория в папку folder на http-сервер.
<p>3. SQLite-база fsmx.db содержит пример структурного автомата и имеет три таблицы:
<p>fsmx 	– таблица переходов с колонками state (номер состояния), command (номер команды) и nextState (номер следующего состояния);
<p>roleStates	– таблица связки ролей с состояниями с колонками state (номер состояния) и role (номер роли);
<p>activeStates	– таблица	с колонкой activeState, содержащая перечень номеров активных состояний структурного автомата.
<p>В базу добавлены представления:
<p>а)	Число входов у точки сборки. Признак точки сборки: command равна -1. 
<p>CREATE VIEW gatecnt(gate, cnt) as select nextstate gate, count(nextstate) cnt from fsmx  where nextState in (select f2.state from fsmx f2 where command = -1) group by nextState
<p>б)	Число повторяющихся активных состояний (используется для активации точки сборки)
<p>CREATE VIEW currentgatecnt(state,cnt) as select activeState, count(activeState) c from activeStates group by activeState having c >1
<p>4. Диаграмма переходов для примера структурного автомата в базе имеет вид:

![s](https://github.com/GrigoryevV/StructuralStateMachine/blob/main/fsmx.png)

<p>5. Запустить движок. Для локального  http-сервера:
<p>localhost/folder/fsmx.py либо либо localhost/folder/fsmx.php

<p>6. Следуя диаграмме переходов, перевести структурный автомат в конечное состояние, равное 0.




