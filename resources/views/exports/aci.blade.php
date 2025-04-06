<table>
    <thead>
        <tr>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
            <th height="164px">&nbsp;</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td height="10px">&nbsp;</td>
        </tr>
        <tr>
            <td height="64px" colspan="3" bgcolor="#00A933"></td>
            <td height="64px" align="center" valign="center" style="font-family:Arial;font-weight:bold;" colspan="6">ELENCO VERIFICATI</td>
            <td height="64px" colspan="3" bgcolor="#FF0000"></td>
        </tr>
        <tr>
            <td height="64px" align="center" valign="center" style="font-family:Arial;font-weight:bold;" colspan="12">{{ $race->title }} - {{ $race->track }}</td>
        </tr>

        @foreach ($participants as $category => $items)
            <tr>
                <td bgcolor="#2A6099" height="32px" align="center" valign="center" style="font-family:Arial;font-weight:bold;color:#ffffff" colspan="12">{{ $category }}</td>
            </tr>
            <tr>
                <td bgcolor="#DDDDDD"></td>
                <td bgcolor="#DDDDDD">No.</td>
                <td bgcolor="#DDDDDD">Conduttore</td>
                <td bgcolor="#DDDDDD">Licenza n.</td>
                <td bgcolor="#DDDDDD">NAZ</td>
                <td bgcolor="#DDDDDD">REG</td>
                <td bgcolor="#DDDDDD">Telaio</td>
                <td bgcolor="#DDDDDD">Motore</td>
                <td bgcolor="#DDDDDD">Pneumatici</td>
                <td bgcolor="#DDDDDD">Concorrente</td>
                <td bgcolor="#DDDDDD">Licenza n.</td>
                <td bgcolor="#DDDDDD">NAZ</td>
            </tr>
            @foreach ($items as $participant)    
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $participant->bib }}</td>
                    <td>{{ str("{$participant->first_name} {$participant->last_name}")->title() }}</td>
                    <td>{{ $participant->driver['licence_number'] }}</td>
                    <td>{{ str($participant->driver['nationality'])->title() }}</td>
                    <td>&nbsp;</td>
                    @foreach ($participant->vehicles as $vehicle)
                        <td>{{ $vehicle['chassis_manufacturer'] }}</td>
                        <td>{{ $vehicle['engine_manufacturer'] }}</td>
                    @endforeach
                    <td>{{ $participant->racingCategory?->tire->name }}</td>
                    @if ($participant->competitor)
                        <td>{{ str("{$participant->competitor['first_name']} {$participant->competitor['last_name']}")->title() }}</td>
                        <td>{{ $participant->competitor['licence_number'] }}</td>
                        <td>{{ str($participant->competitor['nationality'])->title() }}</td>
                    @endif  
                </tr>
            @endforeach
            <tr>
                <td height="32px">&nbsp;</td>
            </tr>
        @endforeach

        <tr>
            <td height="32px">&nbsp;</td>
        </tr>
        <tr>
            <td height="80px" colspan="8"></td>
            <td height="80px" colspan="3" align="center" valign="top" style="font-family:Arial;font-size:6;border: 1px solid #000000">DIRETTORE DI GARA</td>
        </tr>
    
    </tbody>
</table>