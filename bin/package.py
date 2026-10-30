from pathlib import Path
import zipfile
root=Path(__file__).resolve().parent.parent
out=root.parent/'care-cms-demo-source.zip'
exclude={'vendor','var','.git','node_modules'}
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for p in root.rglob('*'):
  rel=p.relative_to(root)
  if p.name.startswith('_ui-') or str(rel).replace('\\','/').startswith('public/bundles/'):continue
  if not p.is_file() or any(part in exclude for part in rel.parts):continue
  if p.name.startswith('.env.') and p.name!='.env.example':continue
  if p.name.endswith(('.db','.sqlite','.sql','.log')):continue
  if str(rel).replace('\\','/').startswith('public/uploads/') and p.name!='.htaccess':continue
  z.write(p,Path('care-cms-demo')/rel)
print(out)
