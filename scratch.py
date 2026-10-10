fields = ['Child Code', 'Child Name (English)', 'Child Name (Korean)', 'Alias', 'Birthday', 'Age', 'Gender', 'Religion', 'Area', 'Office Code', 'Office Name', 'Service State', 'Sponsor State', 'Guardian Type', 'Caregiver', 'Curriculum', 'Grade', 'Favorite Subject', 'Pass/Fail', 'Dream', 'Dream Description', 'Favorite Activity', 'Health', 'Health Description', 'Disability Type', 'Disability Description']
html = ""
for field in fields:
    id_name = field.lower().replace(' ', '_').replace('(', '').replace(')', '').replace('/', '_')
    type_attr = 'date' if field == 'Birthday' else ('number' if field == 'Age' else 'text')
    if 'Description' in field:
        html += f'<div class="sm:col-span-2"><label for="{id_name}" class="block text-xs font-medium text-slate-700">{field}</label><textarea id="{id_name}" name="{id_name}" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea></div>\n'
    elif field in ['Gender', 'Pass/Fail']:
        options = '<option value="M">Male</option><option value="F">Female</option>' if field == 'Gender' else '<option value="Pass">Pass</option><option value="Fail">Fail</option>'
        html += f'<div><label for="{id_name}" class="block text-xs font-medium text-slate-700">{field}</label><select id="{id_name}" name="{id_name}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{options}</select></div>\n'
    else:
        html += f'<div><label for="{id_name}" class="block text-xs font-medium text-slate-700">{field}</label><input type="{type_attr}" id="{id_name}" name="{id_name}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></div>\n'

with open("form_fields.html", "w") as f:
    f.write(html)
