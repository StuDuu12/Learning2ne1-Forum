(() => {
    const schemaInput = document.getElementById('schemaInput');
    const diagramCanvas = document.getElementById('diagramCanvas');
    const relationList = document.getElementById('relationList');
    const projectList = document.getElementById('projectList');
    const projectName = document.getElementById('projectName');
    const statusText = document.getElementById('statusText');
    const saveBtn = document.getElementById('saveBtn');
    const newBtn = document.getElementById('newBtn');
    const deleteBtn = document.getElementById('deleteBtn');

    let selectedProjectId = '';

    function escapeHtml(str) {
        return str
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function parseSchema(rawText) {
        const lines = rawText.split('\n');
        const tables = [];
        const refs = [];
        let currentTable = null;

        lines.forEach((line) => {
            const trimmed = line.trim();
            if (!trimmed) return;

            const tableMatch = trimmed.match(/^Table\s+(\w+)\s*\{$/i);
            if (tableMatch) {
                currentTable = { name: tableMatch[1], columns: [] };
                tables.push(currentTable);
                return;
            }

            if (trimmed === '}') {
                currentTable = null;
                return;
            }

            const refMatch = trimmed.match(/^Ref:\s*(\w+\.\w+)\s*>\s*(\w+\.\w+)$/i);
            if (refMatch) {
                refs.push({ from: refMatch[1], to: refMatch[2] });
                return;
            }

            if (currentTable) {
                const colMatch = trimmed.match(/^(\w+)\s+([\w()]+)(?:\s+\[(.+)\])?$/);
                if (colMatch) {
                    currentTable.columns.push({
                        name: colMatch[1],
                        type: colMatch[2],
                        attrs: colMatch[3] || ''
                    });
                    const inlineRef = (colMatch[3] || '').match(/ref:\s*>\s*(\w+\.\w+)/i);
                    if (inlineRef) {
                        refs.push({ from: `${currentTable.name}.${colMatch[1]}`, to: inlineRef[1] });
                    }
                }
            }
        });

        return { tables, refs };
    }

    function renderDiagram() {
        const schema = parseSchema(schemaInput.value);

        if (!schema.tables.length) {
            diagramCanvas.innerHTML = '<p style="color:#94a3b8">Chưa parse được bảng nào. Kiểm tra cú pháp Table ... { ... }</p>';
            relationList.innerHTML = '<li>Không có quan hệ.</li>';
            return;
        }

        diagramCanvas.innerHTML = schema.tables.map((table) => {
            const cols = table.columns.map((c) => {
                const isPk = c.attrs.toLowerCase().includes('pk');
                const isFk = c.attrs.toLowerCase().includes('ref:');
                return `<li><strong>${escapeHtml(c.name)}</strong> : ${escapeHtml(c.type)}
                    ${isPk ? '<span class="badge pk">PK</span>' : ''}
                    ${isFk ? '<span class="badge fk">FK</span>' : ''}
                </li>`;
            }).join('');

            return `<article class="table-card"><h4>${escapeHtml(table.name)}</h4><ul>${cols}</ul></article>`;
        }).join('');

        relationList.innerHTML = schema.refs.length
            ? schema.refs.map((ref) => `<li>${escapeHtml(ref.from)} → ${escapeHtml(ref.to)}</li>`).join('')
            : '<li>Không có quan hệ.</li>';
    }

    async function postForm(data) {
        const body = new URLSearchParams(data);
        const res = await fetch('dbdiagram.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        return await res.json();
    }

    async function loadProjects() {
        const res = await postForm({ action: 'list' });
        if (!res.success) return;

        projectList.innerHTML = res.projects.map((p) => {
            const active = p.id === selectedProjectId ? 'active' : '';
            return `<li class="project-item ${active}" data-id="${escapeHtml(p.id)}">
                <strong>${escapeHtml(p.name)}</strong>
                <span class="time">${new Date(p.updated_at).toLocaleString('vi-VN')}</span>
            </li>`;
        }).join('');

        projectList.querySelectorAll('.project-item').forEach((el) => {
            el.addEventListener('click', () => {
                const id = el.getAttribute('data-id');
                const project = res.projects.find((x) => x.id === id);
                if (!project) return;

                selectedProjectId = project.id;
                projectName.value = project.name;
                schemaInput.value = project.schema;
                deleteBtn.disabled = false;
                statusText.textContent = `Đã mở project: ${project.name}`;
                renderDiagram();
                loadProjects();
            });
        });
    }

    saveBtn.addEventListener('click', async () => {
        const name = projectName.value.trim();
        if (!name) {
            statusText.textContent = 'Vui lòng nhập tên project.';
            return;
        }

        const res = await postForm({
            action: 'save',
            name,
            schema: schemaInput.value,
            project_id: selectedProjectId
        });

        if (res.success) {
            selectedProjectId = res.project.id;
            deleteBtn.disabled = false;
            statusText.textContent = `Đã lưu lúc ${new Date().toLocaleTimeString('vi-VN')}`;
            await loadProjects();
        } else {
            statusText.textContent = res.message || 'Lưu thất bại.';
        }
    });

    deleteBtn.addEventListener('click', async () => {
        if (!selectedProjectId) return;
        const ok = confirm('Xóa project hiện tại?');
        if (!ok) return;

        const res = await postForm({ action: 'delete', project_id: selectedProjectId });
        if (res.success) {
            selectedProjectId = '';
            projectName.value = '';
            deleteBtn.disabled = true;
            statusText.textContent = 'Đã xóa project.';
            await loadProjects();
        }
    });

    newBtn.addEventListener('click', () => {
        selectedProjectId = '';
        deleteBtn.disabled = true;
        projectName.value = '';
        statusText.textContent = 'Project mới.';
    });

    schemaInput.addEventListener('input', renderDiagram);

    renderDiagram();
    loadProjects();
})();
