# Движок структурного автомата
<p>1.Установить на http-сервере поддержку sqlite, php- и python- скриптов.
<p>2. Поместить все файлы из этого репозитория в папку folder на http-сервер
<p>3. Запустить движок. На локальном  http-сервере
<p>4. localhost/folder/fsmx.py либо либо localhost/folder/fsmx.php
<p>5. SQLite-база SQLite-база fsmx.db содержит три таблицы:
•	fsmx 	– таблица переходов с колонками state (номер состояния), command (номер команды) и nextState (номер следующего состояния);
•	roleStates	– таблица связки ролей с состояниями с колонками state (номер состояния) и role (номер роли);
•	activeState	– таблица	с колонкой, содержащая перечень номеров активных состояний структурного автомата.
В базу добавлены представления:
1.	Число входов у точки сборки. Признак точка сборки: command равна -1. 
CREATE VIEW gatecnt(gate, cnt) as 
select nextstate gate, count(nextstate) cnt from fsmx  
where nextState in (select f2.state from fsmx f2 where command = -1)
group by nextState
2.	Число повторяющихся активных состояний (используется для активации точки сборки)
CREATE VIEW currentgatecnt(state,cnt) as
select activeState, count(activeState) c from activeStates group by activeState having c >1




![s](https://github.com/GrigoryevV/StructuralStateMachine/blob/main/fsmx.png)



