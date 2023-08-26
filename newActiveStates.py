#! C:/Users/P/anaconda3/python
print('Content-type: text/html; charset=utf-8\n\n')
import sqlite3
con = sqlite3.connect('fsmx.db')
cur = con.cursor()
cur.execute('delete from activeStates')
cur.execute('insert into activeStates(activeState) values (1)')
con.commit()

