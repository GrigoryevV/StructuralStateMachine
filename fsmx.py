#! C:/Users/p/anaconda3/python
print('Content-type: text/html; charset=utf-8\n\n')
print('<img src=fsmx1.png height="50%">')
print('<p><h2>Active states:</h2>')
import os
import sqlite3
import cgi

form = cgi.FieldStorage()
#Я
self=os.path.basename(__file__)
refresh = '<html><head><meta http-equiv=refresh content=0;url=http://localhost/%s></head></html>' % self
con = sqlite3.connect('fsmx1.db')
con.row_factory = sqlite3.Row
cur = con.cursor()
#Показываем ссылки для перехода в активные состояния
cur.execute('select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state')
rows = cur.fetchall()
for row in rows:
    r=row['role']
    ast=row['activeState']
    print('<p><a href=%s?role=%d&ast=%d>State=%d for role=%d.</a>' % (self,r,ast,ast,r))

print('<p>Select to go!</p>')
#Показываем выбранное  активное состояние
if form.getfirst('role'):
	role = form.getfirst('role')
	ast =form.getfirst('ast')
	print('<p>-----------------------------------------------------------------------------------')
	print('<p><h2>This is the web page for state=%s and role=%s.</h2></p>' % (ast, role))
	print('<p><h3>Here will be many web elements for input and editing: data grids, charts, etc.</h3></p>')
	print('<br>')
	print('<p><h3>Commands for transition to other states:</h3>')
	#Из состояния команда идёт в одно следующее состояние
	cur.execute('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state=%s and role=%s group by command having c=1' % (ast, role))
	#Перебираем команды исходящие состояния
	rows = cur.fetchall()
	for row in rows:
		cmd=row['command']
		#Формируем ссылку для перехода в другое состояние
		cur.execute('select nextState from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state=%s and role=%s and command=%s' % (ast, role, cmd))
		nextState = cur.fetchone()['nextState']
		print('<p><a href=%s?command=%s&ast=%s>Command = %s:</a>' % (self,cmd, ast, cmd))
		cur.execute('select role from roleStates where state=%s' % nextState)
		print (' next state = %s ' % nextState)
		nextRole = cur.fetchone()['role']
		#Показываем, какое будет после выполнения команды следующее состояние и связанная с ним роль
		print ('for role = %s.' % nextRole)
	#Из состояния команда идёт в несколько следующих состояний
	cur.execute('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state=%s and role=%s group by command having c>1 order by command' % (ast, role))
	#Перебираем команды исходящие из состояния
	rows = cur.fetchall()
	for row in rows:
		cmd=row['command']
		#Формируем ссылку для прехода в другИЕ состояниЯ
		print( '<p><a href=%s?commandX=%s&ast=%s>Command = %s:</a>' % (self, cmd, ast, cmd))
		#Показываем, какИЕ будУТ после выполнения команды следующИЕ состояниЯ и связаннЫЕ с нимИ ролИ
		cur.execute('select nextState, role from fsmx LEFT JOIN roleStates ON fsmx.state=roleStates.state where fsmx.state=%s and role=%s and command=%s' % (ast, role, cmd))
		rows1 = cur.fetchall()
		for row1 in rows1:
			nextState = row1['nextState']
			print (' next state = %s ' % nextState)
			cur.execute('select role from roleStates where state=%s' % nextState)
			nextRole = cur.fetchone()['role']
			print('for role = %s,' % nextRole)
#Отрабатываем команду перехода в следующее состояние
if form.getfirst('command'):
	command=form.getfirst('command')
	ast=form.getfirst('ast')
	#Удаляем состояние из активных состояний
	cur.execute('delete from activeStates where activeState=%s' % ast)
	con.commit()
	cur.execute('select nextState from fsmx where state=%s and command = %s' % (ast, command))
	nextState = cur.fetchone()['nextState']
	#Добавляем следующее состояние в активные состояния
	cur.execute('insert into activeStates(activeState) values (%s)' % nextState)
	con.commit()
	#Проверяем, что следующее состояние - это шлюз у которого все входы активны
	cur.execute('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate=%s' % nextState)
	gate = cur.fetchone()
	if gate is not None:
		gate=gate['gate']
		#Удаляем шлюз из активных состояний
		cur.execute('delete from activeStates where activeState=%s' % gate)
		con.commit()
		#Активируем все состояния, выходящие из шлюза
		cur.execute('select nextState from fsmx where state=%s' % gate)
		rows = cur.fetchall()
		for row in rows:
			cur.execute('insert into activeStates(activeState) values (%s)' % row['nextState'])
		con.commit()
	print(refresh)
#Отрабатываем команду перехода в следующИЕ состояниЯ
if form.getfirst('commandX'):
	command=form.getfirst('commandX')
	ast=form.getfirst('ast')
	#Удаляем состояние из активных состояний
	cur.execute('delete from activeStates where activeState=%s' % ast)
	con.commit()
	#Перебираем следующие состояния
	cur.execute('select nextState from fsmx where state=%s and command = %s' % (ast, command))
	rows = cur.fetchall()
	for row in rows:
		nextState=row['nextState']
		cur.execute('insert into activeStates(activeState) values (%s)' % nextState )
		con.commit()
		#Проверяем, что следующее состояние - это шлюз у которого все входы активны
		cur.execute('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate=%s' % nextState)
		gate = cur.fetchone()
		if gate is not None:
			gate=gate['gate']
			#Удаляем шлюз из активных состояний
			cur.execute('delete from activeStates where activeState=%s' % gate)
			con.commit()
			#Активируем все состояния, выходящие из шлюза
			cur.execute('select nextState from fsmx where state=%s' % gate)
			rows1 = cur.fetchall()
			for row1 in rows1:
				cur.execute('insert into activeStates(activeState) values (%s)' % row1['nextState'])
			con.commit()
	print(refresh)
cur.close()
con.close()


