#! C:/Users/P/anaconda3/python
print('Content-type: text/html; charset=utf-8\n\n')
print('Active states:')
import os
import sqlite3
import cgi

form = cgi.FieldStorage()
#Я
self=os.path.basename(__file__)
refresh = '<html><head><meta http-equiv=refresh content=0;url=http://localhost/%s></head></html>' % self
con = sqlite3.connect('fsmx.db')
con.row_factory = sqlite3.Row
cur = con.cursor()
cur.execute('select activeState, role from activeStates, roleStates where activeStates.activeState=roleStates.state')
rows = cur.fetchall()
for row in rows:
    r=row['role']
    ast=row['activeState']
    print('<p><a href=%s?role=%d&ast=%d>ActiveState=%d. Role=%d.</a>' % (self,r,ast,ast,r))

if form.getfirst('role'):
	role = form.getfirst('role')
	ast =form.getfirst('ast')
	print('<p>-----------------------------------------------------------------------------------')
	print('<p>Web page for activeState=%s, role=%s.<p>' % (ast, role))
	print('<p><h2>There will be many web elements for input and editing: data grids, charts, etc.</h2>')
	cur.execute('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state=%s and role=%s group by command having c=1' % (ast, role))
	rows = cur.fetchall()
	for row in rows:
		cmd=row['command']
		cur.execute('select nextState from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state=%s and role=%s and command=%s' % (ast, role, cmd))
		nextState = cur.fetchone()['nextState']
		print('<p><a href=%s?command=%s&ast=%s>Command = %s</a>' % (self,cmd, ast, cmd))
		sql = 'select role from roleStates where state=%s' % nextState
		#print(sql)
		cur.execute(sql)
		nextRole = cur.fetchone()['role']
		print (' Next State = %s for role = %s' % (nextState,nextRole ))
	cur.execute('select command, count(command) c from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state=%s and role=%s group by command having c>1 order by command' % (ast, role))
	rows = cur.fetchall()
	for row in rows:
		cmd=row['command']
		print( '<p><a href=%s?commandX=%s&ast=%s>Command =%s</a>' % (self, cmd, ast, cmd))
		print('In parallel: ')
		cur.execute('select nextState, role from fsmx, roleStates where fsmx.state=roleStates.state and fsmx.state=%s and role=%s and command=%s' % (ast, role, cmd))
		rows1 = cur.fetchall()
		for row1 in rows1:
			nextState = row1['nextState']
			cur.execute('select role from roleStates where state=%s' % nextState)
			nextRole = cur.fetchone()['role']
			print('Next State = %s for role = %s' % (nextState, nextRole))

if form.getfirst('command'):
	command=form.getfirst('command')
	ast=form.getfirst('ast')
	cur.execute('delete from activeStates where activeState=%s' % ast)
	con.commit()
	cur.execute('select nextState from fsmx where state=%s and command = %s' % (ast, command))
	nextState = cur.fetchone()['nextState']
	cur.execute('insert into activeStates(activeState) values (%s)' % nextState)
	con.commit()
	cur.execute('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate=%s' % nextState)
	gate = cur.fetchone()
	if gate is not None:
		gate=gate['gate']
		cur.execute('delete from activeStates where activeState=%s' % gate)
		con.commit()
		cur.execute('select nextState from fsmx where state=%s' % gate)
		rows = cur.fetchall()
		for row in rows:
			cur.execute('insert into activeStates(activeState) values (%s)' % row['nextState'])
		con.commit()
	print(refresh)

if form.getfirst('commandX'):
	command=form.getfirst('commandX')
	ast=form.getfirst('ast')
	cur.execute('delete from activeStates where activeState=%s' % ast)
	con.commit()
	cur.execute('select nextState from fsmx where state=%s and command = %s' % (ast, command))
	rows = cur.fetchall()
	for row in rows:
		nextState=row['nextState']
		cur.execute('insert into activeStates(activeState) values (%s)' % nextState )
		con.commit()
		cur.execute('select gate from gatecnt, currentgatecnt where gatecnt.cnt=currentgatecnt.cnt and gatecnt.gate=%s' % nextState)
		gate = cur.fetchone()
		if gate is not None:
			gate=gate['gate']
			cur.execute('delete from activeStates where activeState=%s' % gate)
			con.commit()
			cur.execute('select nextState from fsmx where state=%s' % gate)
			rows1 = cur.fetchall()
			for row1 in rows1:
				cur.execute('insert into activeStates(activeState) values (%s)' % row1['nextState'])
			con.commit()
	print(refresh)
cur.close()
con.close()


