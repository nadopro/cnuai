<!-- network.php : index.php 본문에서 include하여 사용 -->

<div class="container-fluid py-3">
    <h2 class="mb-3">인물 관계망</h2>

    <div class="mb-3">
        <span class="me-4">
            <span style="display:inline-block; width:35px; border-top:3px solid red; vertical-align:middle;"></span>
            부자/모자
        </span>
        <span class="me-4">
            <span style="display:inline-block; width:35px; border-top:3px dashed blue; vertical-align:middle;"></span>
            친구
        </span>
        <span class="me-4">
            <span style="display:inline-block; width:35px; border-top:3px solid green; vertical-align:middle;"></span>
            사제
        </span>
        <span>
            <span style="display:inline-block; width:35px; border-top:3px solid #FF00FF; vertical-align:middle;"></span>
            기타
        </span>
    </div>

    <div class="border rounded bg-white">
        <svg id="networkDiagram" width="100%" height="650" style="display:block; cursor:grab;"></svg>
    </div>

    <div class="text-secondary small mt-2">
        마우스 휠: 확대/축소 · 빈 영역 드래그: 화면 이동 · 인물 이름 드래그: 노드 이동
    </div>
</div>

<script>
(function () {
    const graphData = {
        "nodes": [
            { "id": "홍길동" },
            { "id": "홍대감" },
            { "id": "이순신" },
            { "id": "이이" },
            { "id": "사임당" },
            { "id": "유성룡" },
            { "id": "황진이" },
            { "id": "서경덕" }
        ],
        "links": [
            { "source": "홍대감", "target": "홍길동", "relation": "아들" },
            { "source": "홍길동", "target": "이순신", "relation": "친구" },
            { "source": "이순신", "target": "유성룡", "relation": "친구" },
            { "source": "홍대감", "target": "유성룡", "relation": "스승" },
            { "source": "사임당", "target": "이이", "relation": "아들" },
            { "source": "서경덕", "target": "황진이", "relation": "스승" },
            { "source": "이순신", "target": "서경덕", "relation": "장인" },
            { "source": "이이", "target": "서경덕", "relation": "스승" }
        ]
    };

    const svg = d3.select("#networkDiagram");
    const svgNode = svg.node();
    const width = svgNode.clientWidth || 1000;
    const height = 650;

    const zoomGroup = svg.append("g");
    const linkGroup = zoomGroup.append("g");
    const labelGroup = zoomGroup.append("g");
    const nodeGroup = zoomGroup.append("g");

    function getLinkColor(relation) {
        if (relation === "아들" || relation === "딸" ||
            relation === "부자" || relation === "모자") {
            return "red";
        }
        if (relation === "친구") {
            return "blue";
        }
        if (relation === "스승" || relation === "제자") {
            return "green";
        }
        return "#FF00FF";
    }

    function getDashArray(relation) {
        return relation === "친구" ? "8,6" : null;
    }

    const link = linkGroup
        .selectAll("line")
        .data(graphData.links)
        .enter()
        .append("line")
        .attr("stroke", d => getLinkColor(d.relation))
        .attr("stroke-width", 2.5)
        .attr("stroke-dasharray", d => getDashArray(d.relation))
        .attr("stroke-opacity", 0.85);

    const linkLabel = labelGroup
        .selectAll("text")
        .data(graphData.links)
        .enter()
        .append("text")
        .text(d => d.relation)
        .attr("font-size", "12px")
        .attr("fill", "#555")
        .attr("text-anchor", "middle")
        .attr("pointer-events", "none")
        .style("paint-order", "stroke")
        .style("stroke", "white")
        .style("stroke-width", "4px");

    const node = nodeGroup
        .selectAll("text")
        .data(graphData.nodes)
        .enter()
        .append("text")
        .text(d => d.id)
        .attr("font-size", "18px")
        .attr("font-weight", "bold")
        .attr("fill", "#222")
        .attr("text-anchor", "middle")
        .attr("dominant-baseline", "middle")
        .style("cursor", "move")
        .style("user-select", "none")
        .style("paint-order", "stroke")
        .style("stroke", "white")
        .style("stroke-width", "5px");

    const simulation = d3.forceSimulation(graphData.nodes)
        .force("link",
            d3.forceLink(graphData.links)
                .id(d => d.id)
                .distance(160)
                .strength(0.8)
        )
        .force("charge",
            d3.forceManyBody()
                .strength(-650)
        )
        .force("center",
            d3.forceCenter(width / 2, height / 2)
        )
        .force("collision",
            d3.forceCollide().radius(55)
        );

    node.call(
        d3.drag()
            .on("start", function (event, d) {
                event.sourceEvent.stopPropagation();

                if (!event.active) {
                    simulation.alphaTarget(0.3).restart();
                }

                d.fx = d.x;
                d.fy = d.y;
            })
            .on("drag", function (event, d) {
                d.fx = event.x;
                d.fy = event.y;
            })
            .on("end", function (event, d) {
                if (!event.active) {
                    simulation.alphaTarget(0);
                }

                d.fx = null;
                d.fy = null;
            })
    );

    const zoom = d3.zoom()
        .scaleExtent([0.3, 5])
        .on("zoom", function (event) {
            zoomGroup.attr("transform", event.transform);
        });

    svg.call(zoom).on("dblclick.zoom", null);

    svg.on("mousedown.cursor", function () {
        d3.select(this).style("cursor", "grabbing");
    });

    svg.on("mouseup.cursor mouseleave.cursor", function () {
        d3.select(this).style("cursor", "grab");
    });

    simulation.on("tick", function () {
        link
            .attr("x1", d => d.source.x)
            .attr("y1", d => d.source.y)
            .attr("x2", d => d.target.x)
            .attr("y2", d => d.target.y);

        node
            .attr("x", d => d.x)
            .attr("y", d => d.y);

        linkLabel
            .attr("x", d => (d.source.x + d.target.x) / 2)
            .attr("y", d => (d.source.y + d.target.y) / 2);
    });

    window.addEventListener("resize", function () {
        const newWidth = svg.node().clientWidth || width;

        simulation.force(
            "center",
            d3.forceCenter(newWidth / 2, height / 2)
        );

        simulation.alpha(0.3).restart();
    });
})();
</script>
