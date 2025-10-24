
import json
import re
import os

def get_education_level(title):
    title = title.lower()
    if 'form 1' in title or 'f1' in title:
        return 'Form 1'
    if 'form 2' in title or 'f2' in title:
        return 'Form 2'
    if 'form 3' in title or 'f3' in title:
        return 'Form 3'
    if 'form 4' in title or 'f4' in title:
        return 'Form 4'
    if 'form 5' in title or 'f5' in title:
        return 'Form 5'
    if 'form 6' in title or 'f6' in title:
        return 'Form 6'
    if 'grade 1' in title:
        return 'Grade 1'
    if 'grade 2' in title:
        return 'Grade 2'
    if 'grade 3' in title:
        return 'Grade 3'
    if 'grade 4' in title:
        return 'Grade 4'
    if 'grade 5' in title:
        return 'Grade 5'
    if 'grade 6' in title:
        return 'Grade 6'
    if 'grade 7' in title:
        return 'Grade 7'
    if 'o level' in title or 'o-level' in title:
        return 'O Level'
    if 'a level' in title or 'a-level' in title:
        return 'A Level'
    if 'igcse' in title:
        return 'IGCSE'
    if 'as & a level' in title:
        return 'AS & A Level'
    if 'starter' in title:
        return 'Starter'
    return ''

files = os.listdir('.')

books = []
csv_rows = ['"image_url","book_title","author","description","price","education_level"']

for file in files:
    if file.lower().endswith((".jpg", ".jpeg", ".png")):
        match = re.match(r"(.*) by (.*)", file, re.IGNORECASE)
        if match:
            title = match.group(1)
            author = match.group(2).replace('.jpg','').replace('.jpeg','').replace('.png','')
            education_level = get_education_level(title)
            
            book = {
                "image_url": file,
                "book_title": title,
                "author": author,
                "description": f"{title} by {author}",
                "price": "0.00"
            }
            books.append(book)
            
            csv_rows.append(f'"{file}","{title}","{author}","{title} by {author}","0.00",""')

json_content = json.dumps(books, indent=4)
csv_content = "\n".join(csv_rows)

with open('books.json', 'w') as f:
    f.write(json_content)

with open('books.csv', 'w') as f:
    f.write(csv_content)

print("books.json and books.csv have been created.")
